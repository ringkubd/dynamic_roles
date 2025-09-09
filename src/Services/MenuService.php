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
        
        return $this->cacheService->remember($cacheKey, function () use ($user) {
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
        }, config('dynamic-roles.menu.cache_ttl', 1800));
    }

    /**
     * Get full menu tree (admin view)
     */
    public function getFullMenuTree(): Collection
    {
        $cacheKey = "full_menu_tree";
        
        return $this->cacheService->remember($cacheKey, function () {
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
        $permissions = Permission::whereIn('id', $permissionIds)->pluck('id');
        $menu->permissions()->attach($permissions);
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
        $roles = Role::whereIn('id', $roleIds)->pluck('id');
        $menu->roles()->attach($roles);
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
        $this->cacheService->clearByTag('menus');
    }
}
