<?php

namespace Anwar\DynamicRoles\Services;

use Anwar\DynamicRoles\Models\DynamicUrl;
use Anwar\DynamicRoles\Models\DynamicPermissionCheck;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UrlPermissionService
{
    protected PermissionCacheService $cacheService;

    public function __construct(PermissionCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Register a new URL with permissions.
     */
    public function registerUrl(
        string $url,
        string $method = 'GET',
        array $permissions = [],
        array $options = []
    ): DynamicUrl {
        $dynamicUrl = DynamicUrl::updateOrCreate(
            ['url' => $url, 'method' => strtoupper($method)],
            array_merge([
                'name' => $options['name'] ?? $this->generateUrlName($url, $method),
                'description' => $options['description'] ?? null,
                'controller' => $options['controller'] ?? null,
                'action' => $options['action'] ?? null,
                'middleware' => $options['middleware'] ?? [],
                'is_active' => $options['is_active'] ?? true,
                'auto_discovered' => $options['auto_discovered'] ?? false,
                'category' => $options['category'] ?? 'api',
                'priority' => $options['priority'] ?? 0,
                'metadata' => $options['metadata'] ?? [],
            ], $options)
        );

        if (!empty($permissions)) {
            $this->assignPermissionsToUrl($dynamicUrl, $permissions);
        }

        $this->clearUrlCache($url, $method);

        return $dynamicUrl;
    }

    /**
     * Auto-discover routes and register them.
     */
    public function autoDiscoverRoutes(): array
    {
        if (!config('dynamic-roles.discovery.enabled', true)) {
            return [];
        }

        $routes = collect(Route::getRoutes()->getRoutes());
        $registered = [];
        $excludedPatterns = config('dynamic-roles.middleware.excluded_patterns', []);

        foreach ($routes as $route) {
            $uri = $route->uri();
            $methods = $route->methods();

            // Skip excluded patterns
            if ($this->isExcludedUrl($uri, $excludedPatterns)) {
                continue;
            }

            foreach ($methods as $method) {
                if (in_array($method, config('dynamic-roles.middleware.excluded_methods', []))) {
                    continue;
                }

                try {
                    $permissions = $this->generatePermissionsFromRoute($route);
                    
                    $dynamicUrl = $this->registerUrl($uri, $method, $permissions, [
                        'name' => $route->getName(),
                        'controller' => $route->getActionName(),
                        'action' => $route->getActionMethod(),
                        'middleware' => $route->gatherMiddleware(),
                        'auto_discovered' => true,
                        'category' => $this->categorizeRoute($route),
                    ]);

                    $registered[] = $dynamicUrl;
                } catch (\Exception $e) {
                    Log::warning('Failed to auto-discover route', [
                        'uri' => $uri,
                        'method' => $method,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        return $registered;
    }

    /**
     * Check if user has permission for URL.
     */
    public function checkUrlPermission($user, string $url, string $method = 'GET'): bool
    {
        // Check cache first
        $cacheKey = $this->getPermissionCacheKey($user->id ?? 0, $url, $method);
        $cached = $this->cacheService->getUserPermissions($cacheKey);
        
        if ($cached !== null) {
            return $cached['granted'] ?? false;
        }

        $granted = $this->performUrlPermissionCheck($user, $url, $method);

        // Cache the result
        $this->cacheService->cacheUserPermissions($cacheKey, [
            'granted' => $granted,
            'checked_at' => now()->toISOString(),
        ]);

        // Log if enabled
        if (config('dynamic-roles.security.log_permission_checks', false)) {
            $this->logPermissionCheck($user, $url, $method, $granted);
        }

        return $granted;
    }

    /**
     * Perform the actual permission check.
     */
    protected function performUrlPermissionCheck($user, string $url, string $method): bool
    {
        if (!$user) {
            return false;
        }

        // Check if permissions are bypassed
        if (config('dynamic-roles.security.bypass_permissions', false)) {
            return true;
        }

        // Check super admin role
        $superAdminRole = config('dynamic-roles.security.super_admin_role', 'super-admin');
        if ($user->hasRole($superAdminRole)) {
            return true;
        }

        // Find matching dynamic URL
        $dynamicUrl = $this->findMatchingUrl($url, $method);
        
        if (!$dynamicUrl) {
            // If auto-register is enabled, register the URL
            if (config('dynamic-roles.middleware.auto_register_urls', true)) {
                $dynamicUrl = $this->registerUrl($url, $method, 
                    config('dynamic-roles.middleware.default_permissions', ['view'])
                );
            } else {
                return false; // No URL found and auto-register disabled
            }
        }

        return $dynamicUrl->userHasAccess($user);
    }

    /**
     * Find matching URL pattern.
     */
    public function findMatchingUrl(string $url, string $method): ?DynamicUrl
    {
        // Try exact match first
        $exactMatch = DynamicUrl::where('url', $url)
            ->where('method', strtoupper($method))
            ->where('is_active', true)
            ->first();

        if ($exactMatch) {
            return $exactMatch;
        }

        // Try pattern matching
        $urls = DynamicUrl::where('method', strtoupper($method))
            ->where('is_active', true)
            ->get();

        foreach ($urls as $dynamicUrl) {
            if ($dynamicUrl->matchesUrl($url, $method)) {
                return $dynamicUrl;
            }
        }

        return null;
    }

    /**
     * Assign permissions to URL.
     */
    public function assignPermissionsToUrl(DynamicUrl $url, array $permissions): void
    {
        $permissionModels = [];
        
        foreach ($permissions as $permission) {
            if (is_string($permission)) {
                $permissionModel = Permission::firstOrCreate(['name' => $permission]);
                $permissionModels[] = $permissionModel->id;
            } elseif (is_object($permission) && isset($permission->id)) {
                $permissionModels[] = $permission->id;
            }
        }

        $url->permissions()->sync($permissionModels);
        $this->clearUrlCache($url->url, $url->method);
    }

    /**
     * Assign roles to URL.
     */
    public function assignRolesToUrl(DynamicUrl $url, array $roles): void
    {
        $roleModels = [];
        
        foreach ($roles as $role) {
            if (is_string($role)) {
                $roleModel = Role::firstOrCreate(['name' => $role]);
                $roleModels[] = $roleModel->id;
            } elseif (is_object($role) && isset($role->id)) {
                $roleModels[] = $role->id;
            }
        }

        $url->roles()->sync($roleModels);
        $this->clearUrlCache($url->url, $url->method);
    }

    /**
     * Get all URLs with their permissions.
     */
    public function getAllUrls(array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = DynamicUrl::with(['permissions', 'roles']);

        if (isset($filters['method'])) {
            $query->where('method', strtoupper($filters['method']));
        }

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('url', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $perPage = $filters['per_page'] ?? config('dynamic-roles.ui.pagination_per_page', 20);
        
        return $query->orderBy('url')->paginate($perPage);
    }

    /**
     * Delete URL and its associations.
     */
    public function deleteUrl(int $urlId): bool
    {
        $url = DynamicUrl::findOrFail($urlId);
        
        // Clear cache
        $this->clearUrlCache($url->url, $url->method);
        
        // Delete associations
        $url->permissions()->detach();
        $url->roles()->detach();
        
        return $url->delete();
    }

    /**
     * Generate permissions from route.
     */
    protected function generatePermissionsFromRoute($route): array
    {
        $action = $route->getActionMethod();
        $patterns = config('dynamic-roles.discovery.permission_patterns', []);
        
        $permissions = [];
        foreach ($patterns as $permission => $actionPatterns) {
            foreach ($actionPatterns as $pattern) {
                if (str_contains(strtolower($action), $pattern)) {
                    $permissions[] = $permission;
                    break;
                }
            }
        }

        return $permissions ?: config('dynamic-roles.middleware.default_permissions', ['view']);
    }

    /**
     * Categorize route based on URI pattern.
     */
    protected function categorizeRoute($route): string
    {
        $uri = $route->uri();
        
        if (str_starts_with($uri, 'api/')) {
            return 'api';
        }
        
        if (str_starts_with($uri, 'admin/')) {
            return 'admin';
        }
        
        return 'web';
    }

    /**
     * Check if URL should be excluded.
     */
    protected function isExcludedUrl(string $url, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (fnmatch($pattern, $url)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Generate URL name from URL and method.
     */
    protected function generateUrlName(string $url, string $method): string
    {
        $name = str_replace(['/', '{', '}', '?'], ['.', '', '', ''], $url);
        $name = strtolower($method) . '.' . trim($name, '.');
        
        return $name;
    }

    /**
     * Log permission check.
     */
    protected function logPermissionCheck($user, string $url, string $method, bool $granted): void
    {
        try {
            DynamicPermissionCheck::create([
                'user_id' => $user->id ?? null,
                'dynamic_url_id' => $this->findMatchingUrl($url, $method)?->id,
                'permission_name' => $url,
                'granted' => $granted,
                'reason' => $granted ? 'Access granted' : 'Access denied',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'checked_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to log permission check', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Clear URL cache.
     */
    protected function clearUrlCache(string $url, string $method): void
    {
        $this->cacheService->clearUrlCache($url, $method);
    }

    /**
     * Get permission cache key.
     */
    protected function getPermissionCacheKey($userId, string $url, string $method): string
    {
        return "user:{$userId}:url:" . md5($url . ':' . $method);
    }
}
