<?php

namespace Anwar\DynamicRoles\Services;

use Anwar\DynamicRoles\Models\DynamicMenu;
use Anwar\DynamicRoles\Services\PermissionCacheService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MenuService
{
    protected PermissionCacheService $cacheService;

    public function __construct(PermissionCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Create a new menu item
     */
    public function createMenu(array $data): DynamicMenu
    {
        $data = $this->validateMenuData($data);

        DB::beginTransaction();
        try {
            $menu = DynamicMenu::create($data);

            // Auto-create permission if enabled
            if (config('dynamic-roles.menu.auto_permissions', true)) {
                $this->createMenuPermission($menu);
            }

            // Assign permissions if provided
            if (isset($data['permissions']) && is_array($data['permissions'])) {
                $this->assignPermissionsToMenu($menu, $data['permissions']);
            }

            // Assign roles if provided
            if (isset($data['roles']) && is_array($data['roles'])) {
                $this->assignRolesToMenu($menu, $data['roles']);
            }

            DB::commit();
            $this->clearMenuCache();

            return $menu->fresh(['permissions', 'roles', 'children', 'parent']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update a menu item
     */
    public function updateMenu(DynamicMenu $menu, array $data): DynamicMenu
    {
        $data = $this->validateMenuData($data, $menu->id);

        DB::beginTransaction();
        try {
            $menu->update($data);

            // Update permissions if provided
            if (isset($data['permissions'])) {
                $this->syncPermissionsToMenu($menu, $data['permissions']);
            }

            // Update roles if provided
            if (isset($data['roles'])) {
                $this->syncRolesToMenu($menu, $data['roles']);
            }

            DB::commit();
            $this->clearMenuCache();

            return $menu->fresh(['permissions', 'roles', 'children', 'parent']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a menu item and its children
     */
    public function deleteMenu(DynamicMenu $menu, bool $deleteChildren = true): bool
    {
        DB::beginTransaction();
        try {
            if ($deleteChildren) {
                // Delete all descendants
                $this->deleteMenuRecursively($menu);
            } else {
                // Move children to parent or root level
                $menu->children()->update(['parent_id' => $menu->parent_id]);
                $menu->delete();
            }

            DB::commit();
            $this->clearMenuCache();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get menu tree for a user
     */
    public function getMenuTreeForUser($user = null): Collection
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return collect();
        }

        $cacheKey = "menu_tree_user_{$user->id}";
        $cacheTtl = config('dynamic-roles.menu.cache_ttl', 1800);

        // Use Laravel's Cache facade with tags for better cache management
        return \Illuminate\Support\Facades\Cache::tags(['menus', 'user_menus', 'menu_tree'])
            ->remember($cacheKey, $cacheTtl, function () use ($user) {
                $allMenus = DynamicMenu::with(['permissions', 'roles'])
                    ->where('is_active', true)
                    ->where('is_visible', true)
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get();

                // Filter menus based on user permissions
                $accessibleMenus = $allMenus->filter(function ($menu) use ($user) {
                    return $this->userCanAccessMenu($user, $menu);
                });

                return $this->buildMenuTree($accessibleMenus);
            });
    }

    /**
     * Get full menu tree (admin view)
     */
    public function getFullMenuTree(): Collection
    {
        $cacheKey = "full_menu_tree";
        $cacheTtl = config('dynamic-roles.menu.cache_ttl', 1800);

        // Use Laravel's Cache facade with tags for better cache management
        return \Illuminate\Support\Facades\Cache::tags(['menus', 'menu_tree'])
            ->remember($cacheKey, $cacheTtl, function () {
                $allMenus = DynamicMenu::with(['permissions', 'roles', 'children', 'parent'])
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get();

                return $this->buildMenuTree($allMenus);
            });
    }

    /**
     * Get menu breadcrumbs for a given menu item
     */
    public function getMenuBreadcrumbs(DynamicMenu $menu): Collection
    {
        $breadcrumbs = collect();
        $current = $menu;

        while ($current) {
            $breadcrumbs->prepend([
                'id' => $current->id,
                'name' => $current->name,
                'label' => $current->label,
                'url' => $current->url,
                'route_name' => $current->route_name,
                'route_params' => $current->route_params,
            ]);
            $current = $current->parent;
        }

        return $breadcrumbs;
    }

    /**
     * Reorder menu items
     */
    public function reorderMenus(array $menuOrder): bool
    {
        DB::beginTransaction();
        try {
            foreach ($menuOrder as $order => $menuId) {
                DynamicMenu::where('id', $menuId)->update(['sort_order' => $order]);
            }

            DB::commit();
            $this->clearMenuCache();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Assign permissions to menu
     */
    public function assignPermissionsToMenu(DynamicMenu $menu, array $permissionIds): void
    {
        $existingPermissionIds = $menu->permissions()->pluck('permissions.id')->toArray();
        $permissionsToAttach = array_diff($permissionIds, $existingPermissionIds);
        $permissionsToSync = array_intersect($permissionIds, $existingPermissionIds);

        if (!empty($permissionsToAttach)) {
            $menu->permissions()->attach($permissionsToAttach);
        }

        if (!empty($permissionsToSync)) {
            $menu->permissions()->sync($permissionsToSync, false);
        }
        $this->clearMenuCache();
    }

    /**
     * Sync permissions to menu
     */
    public function syncPermissionsToMenu(DynamicMenu $menu, array $permissionIds): void
    {
        $permissions = Permission::whereIn('id', $permissionIds)->pluck('id');
        $menu->permissions()->sync($permissions);
        $this->clearMenuCache();
    }

    /**
     * Assign roles to menu
     */
    public function assignRolesToMenu(DynamicMenu $menu, array $roleIds): void
    {
        $existingRoleIds = $menu->roles()->pluck('roles.id')->toArray();
        $rolesToAttach = array_diff($roleIds, $existingRoleIds);
        $rolesToSync = array_intersect($roleIds, $existingRoleIds);

        if (!empty($rolesToAttach)) {
            $menu->roles()->attach($rolesToAttach);
        }

        if (!empty($rolesToSync)) {
            $menu->roles()->sync($rolesToSync, false);
        }
        $this->clearMenuCache();
    }


    /**
     * Sync roles to menu
     */
    public function syncRolesToMenu(DynamicMenu $menu, array $roleIds): void
    {
        $roles = Role::whereIn('id', $roleIds)->pluck('id');
        $menu->roles()->sync($roles);
        $this->clearMenuCache();
    }

    /**
     * Check if user can access menu
     */
    protected function userCanAccessMenu($user, DynamicMenu $menu): bool
    {
        // Check if menu has specific roles assigned
        if ($menu->roles->isNotEmpty()) {
            if (!$user->hasAnyRole($menu->roles->pluck('name')->toArray())) {
                return false;
            }
        }

        // Check if menu has specific permissions assigned
        if ($menu->permissions->isNotEmpty()) {
            if (!$user->hasAnyPermission($menu->permissions->pluck('name')->toArray())) {
                return false;
            }
        }

        // If no specific permissions or roles, allow access
        return true;
    }

    /**
     * Build hierarchical menu tree
     */
    protected function buildMenuTree(Collection $menus, $parentId = null): Collection
    {
        return $menus
            ->where('parent_id', $parentId)
            ->map(function ($menu) use ($menus) {
                $menuArray = $menu->toArray();
                $menuArray['children'] = $this->buildMenuTree($menus, $menu->id);
                return $menuArray;
            })
            ->values();
    }

    /**
     * Validate menu data
     */
    protected function validateMenuData(array $data, $excludeId = null): array
    {
        // Basic validation
        if (empty($data['name'])) {
            throw new \InvalidArgumentException('Menu name is required');
        }

        if (empty($data['label'])) {
            throw new \InvalidArgumentException('Menu label is required');
        }

        // Check for unique name
        $query = DynamicMenu::where('name', $data['name']);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw new \InvalidArgumentException('Menu name must be unique');
        }

        // Validate parent relationship (prevent circular references)
        if (isset($data['parent_id']) && $data['parent_id'] && $excludeId) {
            if (!$this->isValidParent($excludeId, $data['parent_id'])) {
                throw new \InvalidArgumentException('Invalid parent selection - would create circular reference');
            }
        }

        // Set defaults
        $data['is_active'] = $data['is_active'] ?? true;
        $data['is_visible'] = $data['is_visible'] ?? true;
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }

    /**
     * Check if parent selection is valid (no circular references)
     */
    protected function isValidParent($menuId, $parentId): bool
    {
        if ($menuId == $parentId) {
            return false;
        }

        $parent = DynamicMenu::find($parentId);
        while ($parent) {
            if ($parent->id == $menuId) {
                return false;
            }
            $parent = $parent->parent;
        }

        return true;
    }

    /**
     * Create auto permission for menu
     */
    protected function createMenuPermission(DynamicMenu $menu): void
    {
        $permissionName = "menu.{$menu->name}";

        if (!Permission::where('name', $permissionName)->exists()) {
            Permission::create([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }
    }

    /**
     * Create multiple menu items at once
     */
    public function createMultipleMenus(array $menusData): array
    {
        $createdMenus = [];
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($menusData as $index => $menuData) {
                try {
                    // Validate menu data
                    $validatedData = $this->validateMenuData($menuData);

                    // Create menu
                    $menu = DynamicMenu::create($validatedData);

                    // Auto-create permission if enabled
                    if (config('dynamic-roles.menu.auto_permissions', true)) {
                        $this->createMenuPermission($menu);
                    }

                    // Assign permissions if provided
                    if (isset($menuData['permissions']) && is_array($menuData['permissions'])) {
                        $this->assignPermissionsToMenu($menu, $menuData['permissions']);
                    }

                    // Assign roles if provided
                    if (isset($menuData['roles']) && is_array($menuData['roles'])) {
                        $this->assignRolesToMenu($menu, $menuData['roles']);
                    }

                    $createdMenus[] = $menu->fresh(['permissions', 'roles', 'children', 'parent']);
                } catch (\Exception $e) {
                    $errors[$index] = [
                        'menu_data' => $menuData,
                        'error' => $e->getMessage()
                    ];
                }
            }

            // If there are any errors, rollback everything
            if (!empty($errors)) {
                DB::rollBack();
                throw new \Exception('Failed to create some menus. All operations rolled back.');
            }

            DB::commit();
            $this->clearMenuCache();

            return [
                'success' => true,
                'created_menus' => $createdMenus,
                'total_created' => count($createdMenus),
                'errors' => []
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'created_menus' => [],
                'total_created' => 0,
                'errors' => $errors,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete menu recursively
     */
    protected function deleteMenuRecursively(DynamicMenu $menu): void
    {
        foreach ($menu->children as $child) {
            $this->deleteMenuRecursively($child);
        }
        $menu->delete();
    }

    /**
     * Clear menu cache
     */
    protected function clearMenuCache(): void
    {
        try {
            // Use Laravel's tag-based cache clearing for supported drivers
            if ($this->supportsCacheTags()) {
                \Illuminate\Support\Facades\Cache::tags(['menus', 'menu_tree', 'user_menus'])->flush();
            }

            // Clear specific menu cache keys
            \Illuminate\Support\Facades\Cache::forget('full_menu_tree');

            // Clear all user-specific menu caches
            $this->clearAllUserMenuCaches();

            // Clear through the permission cache service as well
            $this->cacheService->clearByTag('menus');
            $this->cacheService->clearByTag('menu_tree');
            $this->cacheService->clearByTag('user_menus');
            $this->cacheService->clearByTag('routes');
            $this->cacheService->clearByTag('permissions');

            // Also clear all permissions cache as menu changes might affect permissions
            $this->cacheService->clearAll();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to clear menu cache completely', [
                'error' => $e->getMessage()
            ]);

            // Fallback: aggressive cache clearing
            $this->clearAllUserMenuCaches();
            $this->cacheService->clearAll();
        }
    }

    /**
     * Check if the current cache driver supports tags
     */
    protected function supportsCacheTags(): bool
    {
        $driver = config('cache.default');
        return in_array($driver, ['redis', 'memcached']);
    }

    /**
     * Force clear all menu-related caches (public method for manual cache clearing)
     */
    public function forceClearAllMenuCaches(): bool
    {
        try {
            // Clear Laravel cache tags
            if ($this->supportsCacheTags()) {
                \Illuminate\Support\Facades\Cache::tags(['menus', 'menu_tree', 'user_menus'])->flush();
            }

            // Clear specific cache keys
            \Illuminate\Support\Facades\Cache::forget('full_menu_tree');

            // If using Redis, clear all menu-related keys directly
            if (config('cache.default') === 'redis') {
                $this->clearAllRedisMenuKeysDirectly();
            }

            // Clear all user menu caches
            $this->clearAllUserMenuCaches();

            // Clear permission cache service
            $this->cacheService->clearAll();

            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to force clear all menu caches', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Clear all Redis menu patterns (aggressive clearing)
     */
    protected function clearAllRedisMenuPatterns(): void
    {
        try {
            $redis = \Illuminate\Support\Facades\Redis::connection('cache');

            // Get prefixes
            $redisPrefix = config('database.redis.options.prefix', '');
            $cachePrefix = config('cache.prefix', '');

            $patterns = [
                '*menu*',
                '*Menu*',
                '*full_menu_tree*',
                '*menu_tree_user_*',
                '*:menu*',
                '*:Menu*',
                '*:full_menu_tree*',
                "{$redisPrefix}{$cachePrefix}*menu*",
                "{$redisPrefix}{$cachePrefix}*Menu*",
                "{$redisPrefix}{$cachePrefix}*full_menu_tree*",
            ];

            foreach ($patterns as $pattern) {
                if (empty($pattern)) continue;
                $keys = $redis->keys($pattern);
                if (!empty($keys)) {
                    $redis->del($keys);
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to clear Redis menu patterns', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Clear all Redis menu keys directly using the debug method
     */
    protected function clearAllRedisMenuKeysDirectly(): void
    {
        try {
            // Use the debug method to find all menu keys
            $debugInfo = $this->debugMenuCacheKeys();

            if (!empty($debugInfo['found_keys'])) {
                // Collect all unique keys
                $allKeys = [];
                foreach ($debugInfo['found_keys'] as $pattern => $keys) {
                    $allKeys = array_merge($allKeys, $keys);
                }

                // Remove duplicates and convert to Laravel cache keys
                $uniqueKeys = array_unique($allKeys);
                $redisPrefix = config('database.redis.options.prefix', '');
                $cachePrefix = config('cache.prefix', '');
                $fullPrefix = $redisPrefix . $cachePrefix . ':';

                foreach ($uniqueKeys as $redisKey) {
                    // Convert Redis key to Laravel cache key
                    if (str_starts_with($redisKey, $fullPrefix)) {
                        $cacheKey = substr($redisKey, strlen($fullPrefix));
                        \Illuminate\Support\Facades\Cache::forget($cacheKey);
                    } else {
                        // Try deleting with Redis directly as fallback
                        $redis = \Illuminate\Support\Facades\Redis::connection('cache');
                        $redis->del($redisKey);
                    }
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to clear Redis menu keys directly', [
                'error' => $e->getMessage()
            ]);

            // Fallback to pattern clearing
            $this->clearAllRedisMenuPatterns();
        }
    }

    /**
     * Debug method to show current menu cache keys (for development/debugging)
     */
    public function debugMenuCacheKeys(): array
    {
        $result = [
            'cache_driver' => config('cache.default'),
            'cache_prefix' => config('cache.prefix'),
            'redis_prefix' => config('database.redis.options.prefix'),
            'dynamic_roles_prefix' => config('dynamic-roles.cache.prefix', 'dynamic_roles'),
            'found_keys' => [],
            'error' => null
        ];

        try {
            if (config('cache.default') === 'redis') {
                $redis = \Illuminate\Support\Facades\Redis::connection('cache');

                // Include Redis prefix in patterns
                $redisPrefix = config('database.redis.options.prefix', '');
                $cachePrefix = config('cache.prefix', '');

                $patterns = [
                    '*menu*',
                    '*Menu*',
                    '*full_menu_tree*',
                    '*menu_tree_user_*',
                    '*:menu*',
                    '*:Menu*',
                    '*:full_menu_tree*',
                    $redisPrefix . $cachePrefix . '*menu*',
                    $redisPrefix . $cachePrefix . '*Menu*',
                ];

                foreach ($patterns as $pattern) {
                    if (empty($pattern)) continue;
                    $keys = $redis->keys($pattern);
                    if (!empty($keys)) {
                        $result['found_keys'][$pattern] = $keys;
                    }
                }
            } else {
                $result['error'] = 'Cache debugging only available for Redis driver';
            }
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Clear all user menu caches
     */
    protected function clearAllUserMenuCaches(): void
    {
        try {
            // For Redis cache driver
            if (config('cache.default') === 'redis') {
                $this->clearRedisUserMenuCaches();
            } else {
                // For other cache drivers, try to clear common user menu cache patterns
                // This is less efficient but works for non-Redis drivers
                $this->clearUserMenuCachesByPattern();
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to clear user menu caches', [
                'error' => $e->getMessage()
            ]);

            // Fallback: clear by tag
            $this->cacheService->clearByTag('user_menus');
        }
    }

    /**
     * Clear user menu caches in Redis
     */
    protected function clearRedisUserMenuCaches(): void
    {
        try {
            $redis = \Illuminate\Support\Facades\Redis::connection('cache');

            // Get prefixes
            $redisPrefix = config('database.redis.options.prefix', '');
            $cachePrefix = config('cache.prefix', '');
            $dynamicRolesPrefix = config('dynamic-roles.cache.prefix', 'dynamic_roles');

            // Clear with various prefix combinations
            $patterns = [
                'menu_tree_user_*',
                "*:menu_tree_user_*",
                "{$dynamicRolesPrefix}:menu_tree_user_*",
                "{$cachePrefix}:menu_tree_user_*",
                "{$redisPrefix}{$cachePrefix}:*menu_tree_user_*",
                "*menu_tree_user_*",
            ];

            foreach ($patterns as $pattern) {
                if (empty($pattern)) continue;
                $keys = $redis->keys($pattern);
                if (!empty($keys)) {
                    $redis->del($keys);
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to clear Redis user menu caches', [
                'error' => $e->getMessage()
            ]);
            // Fallback to pattern clearing
            $this->clearUserMenuCachesByPattern();
        }
    }

    /**
     * Clear user menu caches by trying common user ID patterns
     */
    protected function clearUserMenuCachesByPattern(): void
    {
        // This is a fallback method for non-Redis drivers
        // In a production environment, you might want to maintain a list of active users
        // or use a more sophisticated cache management approach

        // Clear common cache keys that might exist
        for ($userId = 1; $userId <= 1000; $userId++) {
            \Illuminate\Support\Facades\Cache::forget("menu_tree_user_{$userId}");
        }

        // Also try to clear with cache prefix
        $prefix = config('dynamic-roles.cache.prefix', 'dynamic_roles');
        for ($userId = 1; $userId <= 1000; $userId++) {
            \Illuminate\Support\Facades\Cache::forget("{$prefix}:menu_tree_user_{$userId}");
        }
    }
}
