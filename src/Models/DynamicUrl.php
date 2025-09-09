<?php

namespace Anwar\DynamicRoles\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DynamicUrl extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'url',
        'method',
        'name',
        'description',
        'controller',
        'action',
        'middleware',
        'is_active',
        'auto_discovered',
        'category',
        'priority',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'auto_discovered' => 'boolean',
        'middleware' => 'array',
        'metadata' => 'array',
        'priority' => 'integer',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Get the table name from config.
     */
    public function getTable(): string
    {
        return config('dynamic-roles.database.tables.dynamic_urls', 'dynamic_urls');
    }

    /**
     * Permissions associated with this URL.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            config('permission.models.permission'),
            config('dynamic-roles.database.tables.dynamic_url_permissions', 'dynamic_url_permissions'),
            'dynamic_url_id',
            'permission_id'
        )->withTimestamps();
    }

    /**
     * Roles that have access to this URL.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            config('permission.models.role'),
            config('dynamic-roles.database.tables.dynamic_role_urls', 'dynamic_role_urls'),
            'dynamic_url_id',
            'role_id'
        )->withTimestamps();
    }

    /**
     * Get permission checks for this URL.
     */
    public function permissionChecks(): HasMany
    {
        return $this->hasMany(DynamicPermissionCheck::class);
    }

    /**
     * Scope for active URLs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for URLs by method.
     */
    public function scopeByMethod($query, string $method)
    {
        return $query->where('method', strtoupper($method));
    }

    /**
     * Scope for URLs by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Check if URL matches the given pattern.
     */
    public function matchesUrl(string $url, string $method = 'GET'): bool
    {
        if (strtoupper($this->method) !== strtoupper($method)) {
            return false;
        }

        // Convert Laravel route parameters to regex
        $pattern = $this->url;
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $pattern);
        $pattern = str_replace('/', '\/', $pattern);
        $pattern = '/^' . $pattern . '$/';

        return preg_match($pattern, $url) === 1;
    }

    /**
     * Get required permissions for this URL.
     */
    public function getRequiredPermissions(): array
    {
        return $this->permissions()->pluck('name')->toArray();
    }

    /**
     * Check if user has access to this URL.
     */
    public function userHasAccess($user): bool
    {
        if (!$user) {
            return false;
        }

        // Check if user has super admin role
        $superAdminRole = config('dynamic-roles.security.super_admin_role', 'super-admin');
        if ($user->hasRole($superAdminRole)) {
            return true;
        }

        // Check direct permissions
        $requiredPermissions = $this->getRequiredPermissions();
        if (empty($requiredPermissions)) {
            return true; // No permissions required
        }

        foreach ($requiredPermissions as $permission) {
            if ($user->hasPermissionTo($permission)) {
                return true;
            }
        }

        // Check role-based access
        $userRoles = $user->roles()->pluck('id')->toArray();
        $urlRoles = $this->roles()->pluck('id')->toArray();

        return !empty(array_intersect($userRoles, $urlRoles));
    }

    /**
     * Auto-generate permissions based on URL pattern.
     */
    public function generatePermissions(): array
    {
        $permissions = [];
        $patterns = config('dynamic-roles.discovery.permission_patterns', []);
        
        foreach ($patterns as $permission => $actionPatterns) {
            foreach ($actionPatterns as $pattern) {
                if (str_contains(strtolower($this->action), $pattern)) {
                    $permissions[] = $permission;
                    break;
                }
            }
        }

        return $permissions ?: config('dynamic-roles.middleware.default_permissions', ['view']);
    }

    /**
     * Get the route cache key.
     */
    public function getCacheKey(): string
    {
        return sprintf(
            '%s:url:%s:%s',
            config('dynamic-roles.cache.prefix', 'dynamic_roles'),
            md5($this->url),
            $this->method
        );
    }
}
