<?php

namespace Anwar\DynamicRoles\Services;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Anwar\DynamicRoles\Models\DynamicUrl;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RolePermissionService
{
    protected PermissionCacheService $cacheService;

    public function __construct(PermissionCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Create a new role with permissions.
     */
    public function createRole(string $name, array $permissions = [], array $options = []): Role
    {
        $role = Role::create([
            'name' => $name,
            'guard_name' => $options['guard_name'] ?? 'web',
        ]);

        if (!empty($permissions)) {
            $this->assignPermissionsToRole($role, $permissions);
        }

        $this->cacheService->clearRoleCache($role->id);

        return $role;
    }

    /**
     * Create a new permission.
     */
    public function createPermission(string $name, array $options = []): Permission
    {
        return Permission::create([
            'name' => $name,
            'guard_name' => $options['guard_name'] ?? 'web',
        ]);
    }

    /**
     * Assign permissions to role.
     */
    public function assignPermissionsToRole(Role $role, array $permissions): void
    {
        $permissionModels = [];

        foreach ($permissions as $permission) {
            if (is_string($permission)) {
                $permissionModel = Permission::firstOrCreate(['name' => $permission]);
                $permissionModels[] = $permissionModel;
            } elseif ($permission instanceof Permission) {
                $permissionModels[] = $permission;
            }
        }

        $role->syncPermissions($permissionModels);
        $this->cacheService->clearRoleCache($role->id);
    }

    /**
     * Remove permissions from role.
     */
    public function removePermissionsFromRole(Role $role, array $permissions): void
    {
        foreach ($permissions as $permission) {
            if (is_string($permission)) {
                $role->revokePermissionTo($permission);
            } elseif ($permission instanceof Permission) {
                $role->revokePermissionTo($permission);
            }
        }

        $this->cacheService->clearRoleCache($role->id);
    }

    /**
     * Assign role to user.
     */
    public function assignRoleToUser($user, $role): void
    {
        if (is_string($role)) {
            $user->assignRole($role);
        } elseif ($role instanceof Role) {
            $user->assignRole($role);
        }

        $this->cacheService->clearUserCache($user->id);
    }

    /**
     * Remove role from user.
     */
    public function removeRoleFromUser($user, $role): void
    {
        if (is_string($role)) {
            $user->removeRole($role);
        } elseif ($role instanceof Role) {
            $user->removeRole($role);
        }

        $this->cacheService->clearUserCache($user->id);
    }

    /**
     * Get all roles with their permissions.
     */
    public function getAllRoles(array $filters = []): Collection
    {
        $query = Role::with('permissions');

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        if (isset($filters['guard_name'])) {
            $query->where('guard_name', $filters['guard_name']);
        }

        return $query->get();
    }

    /**
     * Get all permissions.
     */
    public function getAllPermissions(array $filters = []): Collection
    {
        $query = Permission::query();

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        if (isset($filters['guard_name'])) {
            $query->where('guard_name', $filters['guard_name']);
        }

        return $query->get();
    }

    /**
     * Get role permissions with caching.
     */
    public function getRolePermissions(Role $role): array
    {
        $cached = $this->cacheService->getRolePermissions($role->id);
        
        if ($cached !== null) {
            return $cached;
        }

        $permissions = $role->permissions()->pluck('name')->toArray();
        
        $this->cacheService->cacheRolePermissions($role->id, $permissions);

        return $permissions;
    }

    /**
     * Get user permissions with caching.
     */
    public function getUserPermissions($user): array
    {
        $cached = $this->cacheService->getUserPermissions($user->id);
        
        if ($cached !== null) {
            return $cached;
        }

        $permissions = $user->getAllPermissions()->pluck('name')->toArray();
        
        $this->cacheService->cacheUserPermissions($user->id, $permissions);

        return $permissions;
    }

    /**
     * Bulk assign permissions to multiple roles.
     */
    public function bulkAssignPermissions(array $rolePermissions): void
    {
        DB::transaction(function () use ($rolePermissions) {
            foreach ($rolePermissions as $roleName => $permissions) {
                $role = Role::where('name', $roleName)->first();
                if ($role) {
                    $this->assignPermissionsToRole($role, $permissions);
                }
            }
        });
    }

    /**
     * Bulk assign roles to multiple users.
     */
    public function bulkAssignRoles(array $userRoles): void
    {
        DB::transaction(function () use ($userRoles) {
            foreach ($userRoles as $userId => $roles) {
                $user = config('auth.providers.users.model')::find($userId);
                if ($user) {
                    foreach ($roles as $role) {
                        $this->assignRoleToUser($user, $role);
                    }
                }
            }
        });
    }

    /**
     * Sync role permissions.
     */
    public function syncRolePermissions(Role $role, array $permissions): void
    {
        $permissionModels = [];

        foreach ($permissions as $permission) {
            if (is_string($permission)) {
                $permissionModel = Permission::firstOrCreate(['name' => $permission]);
                $permissionModels[] = $permissionModel;
            } elseif ($permission instanceof Permission) {
                $permissionModels[] = $permission;
            }
        }

        $role->syncPermissions($permissionModels);
        $this->cacheService->clearRoleCache($role->id);
    }

    /**
     * Get permission usage statistics.
     */
    public function getPermissionStats(): array
    {
        return [
            'total_roles' => Role::count(),
            'total_permissions' => Permission::count(),
            'total_urls' => DynamicUrl::count(),
            'active_urls' => DynamicUrl::where('is_active', true)->count(),
            'auto_discovered_urls' => DynamicUrl::where('auto_discovered', true)->count(),
            'permissions_per_role' => $this->getPermissionsPerRole(),
            'roles_per_user' => $this->getRolesPerUser(),
        ];
    }

    /**
     * Get average permissions per role.
     */
    protected function getPermissionsPerRole(): float
    {
        $roles = Role::withCount('permissions')->get();
        
        if ($roles->isEmpty()) {
            return 0;
        }

        return $roles->avg('permissions_count');
    }

    /**
     * Get average roles per user.
     */
    protected function getRolesPerUser(): float
    {
        $userModel = config('auth.providers.users.model');
        $users = $userModel::withCount('roles')->get();
        
        if ($users->isEmpty()) {
            return 0;
        }

        return $users->avg('roles_count');
    }

    /**
     * Clean up orphaned permissions and roles.
     */
    public function cleanupOrphaned(): array
    {
        $cleaned = [
            'orphaned_permissions' => 0,
            'unused_roles' => 0,
        ];

        // Clean up permissions not assigned to any role or URL
        $orphanedPermissions = Permission::whereDoesntHave('roles')
            ->whereDoesntHave('dynamicUrls')
            ->get();

        foreach ($orphanedPermissions as $permission) {
            $permission->delete();
            $cleaned['orphaned_permissions']++;
        }

        // Clean up roles not assigned to any user
        $userModel = config('auth.providers.users.model');
        $unusedRoles = Role::whereDoesntHave('users')->get();

        foreach ($unusedRoles as $role) {
            // Don't delete system roles
            $systemRoles = ['super-admin', 'admin', 'user'];
            if (!in_array($role->name, $systemRoles)) {
                $role->delete();
                $cleaned['unused_roles']++;
            }
        }

        return $cleaned;
    }

    /**
     * Export roles and permissions configuration.
     */
    public function exportConfiguration(): array
    {
        $roles = Role::with('permissions')->get();
        $urls = DynamicUrl::with(['permissions', 'roles'])->get();

        return [
            'roles' => $roles->map(function ($role) {
                return [
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                    'permissions' => $role->permissions->pluck('name')->toArray(),
                ];
            })->toArray(),
            'urls' => $urls->map(function ($url) {
                return [
                    'url' => $url->url,
                    'method' => $url->method,
                    'name' => $url->name,
                    'description' => $url->description,
                    'category' => $url->category,
                    'is_active' => $url->is_active,
                    'permissions' => $url->permissions->pluck('name')->toArray(),
                    'roles' => $url->roles->pluck('name')->toArray(),
                ];
            })->toArray(),
        ];
    }

    /**
     * Import roles and permissions configuration.
     */
    public function importConfiguration(array $config): void
    {
        DB::transaction(function () use ($config) {
            // Import roles
            foreach ($config['roles'] ?? [] as $roleConfig) {
                $role = Role::firstOrCreate([
                    'name' => $roleConfig['name'],
                    'guard_name' => $roleConfig['guard_name'] ?? 'web',
                ]);

                if (!empty($roleConfig['permissions'])) {
                    $this->assignPermissionsToRole($role, $roleConfig['permissions']);
                }
            }

            // Import URLs
            foreach ($config['urls'] ?? [] as $urlConfig) {
                $url = DynamicUrl::updateOrCreate(
                    ['url' => $urlConfig['url'], 'method' => $urlConfig['method']],
                    [
                        'name' => $urlConfig['name'],
                        'description' => $urlConfig['description'] ?? null,
                        'category' => $urlConfig['category'] ?? 'api',
                        'is_active' => $urlConfig['is_active'] ?? true,
                    ]
                );

                if (!empty($urlConfig['permissions'])) {
                    $permissionModels = [];
                    foreach ($urlConfig['permissions'] as $permissionName) {
                        $permission = Permission::firstOrCreate(['name' => $permissionName]);
                        $permissionModels[] = $permission->id;
                    }
                    $url->permissions()->sync($permissionModels);
                }

                if (!empty($urlConfig['roles'])) {
                    $roleModels = [];
                    foreach ($urlConfig['roles'] as $roleName) {
                        $role = Role::firstOrCreate(['name' => $roleName]);
                        $roleModels[] = $role->id;
                    }
                    $url->roles()->sync($roleModels);
                }
            }
        });

        // Clear all caches after import
        $this->cacheService->clearAll();
    }
}
