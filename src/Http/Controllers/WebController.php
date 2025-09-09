<?php

namespace Anwar\DynamicRoles\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Anwar\DynamicRoles\Services\RolePermissionService;
use Anwar\DynamicRoles\Services\UrlPermissionService;
use Anwar\DynamicRoles\Services\MenuService;
use Anwar\DynamicRoles\Services\PermissionCacheService;
use Anwar\DynamicRoles\Models\DynamicUrl;
use Anwar\DynamicRoles\Models\DynamicMenu;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class WebController extends Controller
{
    public function __construct(
        private RolePermissionService $rolePermissionService,
        private UrlPermissionService $urlPermissionService,
        private MenuService $menuService,
        private PermissionCacheService $cacheService
    ) {}

    /**
     * Dashboard overview
     */
    public function dashboard(): View
    {
        $stats = [
            'roles' => Role::count(),
            'permissions' => Permission::count(),
            'urls' => DynamicUrl::count(),
            'menus' => DynamicMenu::count(),
        ];

        return view('dynamic-roles::dashboard', compact('stats'));
    }

    /**
     * Roles listing page
     */
    public function roles(): View
    {
        $roles = Role::with('permissions')->paginate(15);
        return view('dynamic-roles::roles.index', compact('roles'));
    }

    /**
     * Show role details
     */
    public function showRole(Role $role): View
    {
        $role->load('permissions', 'users');
        $allPermissions = Permission::all();
        
        return view('dynamic-roles::roles.show', compact('role', 'allPermissions'));
    }

    /**
     * Edit role permissions
     */
    public function editRole(Role $role): View
    {
        $role->load('permissions');
        $allPermissions = Permission::all()->groupBy(function ($permission) {
            return explode('-', $permission->name)[0] ?? 'general';
        });
        
        return view('dynamic-roles::roles.edit', compact('role', 'allPermissions'));
    }

    /**
     * Update role permissions
     */
    public function updateRole(Request $request, Role $role): RedirectResponse
    {
        $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'integer|exists:permissions,id'
        ]);

        try {
            $this->rolePermissionService->assignPermissionsToRole(
                $role,
                $request->input('permissions', [])
            );

            return redirect()
                ->route('dynamic-roles.roles.show', $role)
                ->with('success', 'Role permissions updated successfully');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to update role permissions: ' . $e->getMessage());
        }
    }

    /**
     * Permissions listing page
     */
    public function permissions(): View
    {
        $permissions = Permission::with('roles')->paginate(15);
        return view('dynamic-roles::permissions.index', compact('permissions'));
    }

    /**
     * URLs listing page
     */
    public function urls(): View
    {
        $urls = DynamicUrl::with(['permissions', 'roles'])->paginate(15);
        return view('dynamic-roles::urls.index', compact('urls'));
    }

    /**
     * Show URL details
     */
    public function showUrl($id): View
    {
        $url = DynamicUrl::with(['permissions', 'roles'])->findOrFail($id);
        $allPermissions = Permission::all();
        $allRoles = Role::all();
        
        return view('dynamic-roles::urls.show', compact('url', 'allPermissions', 'allRoles'));
    }

    /**
     * Edit URL permissions
     */
    public function editUrl($id): View
    {
        $url = DynamicUrl::with(['permissions', 'roles'])->findOrFail($id);
        $allPermissions = Permission::all();
        $allRoles = Role::all();
        
        return view('dynamic-roles::urls.edit', compact('url', 'allPermissions', 'allRoles'));
    }

    /**
     * Update URL permissions
     */
    public function updateUrl(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'integer|exists:permissions,id',
            'roles' => 'array',
            'roles.*' => 'integer|exists:roles,id'
        ]);

        try {
            if ($request->has('permissions')) {
                $this->urlPermissionService->assignPermissionsToUrl(
                    $id,
                    $request->input('permissions', [])
                );
            }

            if ($request->has('roles')) {
                $this->urlPermissionService->assignRolesToUrl(
                    $id,
                    $request->input('roles', [])
                );
            }

            return redirect()
                ->route('dynamic-roles.urls.show', $id)
                ->with('success', 'URL permissions updated successfully');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to update URL permissions: ' . $e->getMessage());
        }
    }

    /**
     * Menus listing page
     */
    public function menus(): View
    {
        $menus = DynamicMenu::with(['permissions', 'roles', 'children'])->whereNull('parent_id')->orderBy('sort_order')->paginate(15);
        return view('dynamic-roles::menus.index', compact('menus'));
    }

    /**
     * Show menu details
     */
    public function showMenu($id): View
    {
        $menu = DynamicMenu::with(['permissions', 'roles', 'children', 'parent'])->findOrFail($id);
        $allPermissions = Permission::all();
        $allRoles = Role::all();
        
        return view('dynamic-roles::menus.show', compact('menu', 'allPermissions', 'allRoles'));
    }

    /**
     * Cache management page
     */
    public function cache(): View
    {
        $stats = $this->cacheService->getStats();
        return view('dynamic-roles::cache.index', compact('stats'));
    }

    /**
     * Clear cache
     */
    public function clearCache(Request $request): RedirectResponse
    {
        try {
            if ($request->input('type') === 'user' && $request->input('user_id')) {
                $this->cacheService->clearUserCache($request->input('user_id'));
                $message = 'User cache cleared successfully';
            } else {
                $this->cacheService->clearAll();
                $message = 'All cache cleared successfully';
            }

            return redirect()
                ->route('dynamic-roles.cache')
                ->with('success', $message);
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to clear cache: ' . $e->getMessage());
        }
    }
}
