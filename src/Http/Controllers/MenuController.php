<?php

namespace Anwar\DynamicRoles\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Anwar\DynamicRoles\Services\MenuService;
use Anwar\DynamicRoles\Models\DynamicMenu;
use Illuminate\Validation\ValidationException;

class MenuController extends Controller
{
    protected MenuService $menuService;

    public function __construct(MenuService $menuService)
    {
        $this->menuService = $menuService;
    }

    /**
     * Get all menus with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $parentId = $request->get('parent_id');
            $isActive = $request->get('is_active');
            $isVisible = $request->get('is_visible');

            $query = DynamicMenu::with(['permissions', 'roles', 'parent', 'children']);

            // Apply filters
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('label', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            if ($parentId !== null) {
                $query->where('parent_id', $parentId);
            }

            if ($isActive !== null) {
                $query->where('is_active', (bool)$isActive);
            }

            if ($isVisible !== null) {
                $query->where('is_visible', (bool)$isVisible);
            }

            $query->orderBy('sort_order')->orderBy('name');

            $menus = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $menus,
                'message' => 'Menus retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve menus: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get menu tree for current user
     */
    public function tree(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id ?? null;
            $fullTree = $request->get('full', false);

            if ($fullTree && $request->user()->can('manage_menus')) {
                $tree = $this->menuService->getFullMenuTree();
            } else {
                $tree = $this->menuService->getMenuTreeForUser($request->user());
            }

            return response()->json([
                'success' => true,
                'data' => $tree,
                'message' => 'Menu tree retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve menu tree: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new menu
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:' . config('dynamic-roles.table_names.dynamic_menus', 'dynamic_menus'),
                'label' => 'required|string|max:255',
                'url' => 'nullable|string|max:500',
                'icon' => 'nullable|string|max:255',
                'route_name' => 'nullable|string|max:255',
                'route_params' => 'nullable|array',
                'parent_id' => 'nullable|exists:' . config('dynamic-roles.table_names.dynamic_menus', 'dynamic_menus') . ',id',
                'sort_order' => 'nullable|integer|min:0',
                'is_active' => 'nullable|boolean',
                'is_visible' => 'nullable|boolean',
                'description' => 'nullable|string',
                'metadata' => 'nullable|array',
                'permissions' => 'nullable|array',
                'permissions.*' => 'exists:permissions,id',
                'roles' => 'nullable|array',
                'roles.*' => 'exists:roles,id',
            ]);

            $menu = $this->menuService->createMenu($validated);

            return response()->json([
                'success' => true,
                'data' => $menu,
                'message' => 'Menu created successfully'
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create menu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create multiple menus at once
     */
    public function storeBulk(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'menus' => 'required|array|min:1|max:50', // Limit to 50 menus at once
                'menus.*.name' => 'required|string|max:255',
                'menus.*.label' => 'required|string|max:255',
                'menus.*.url' => 'nullable|string|max:500',
                'menus.*.icon' => 'nullable|string|max:255',
                'menus.*.route_name' => 'nullable|string|max:255',
                'menus.*.route_params' => 'nullable|array',
                'menus.*.parent_id' => 'nullable|exists:' . config('dynamic-roles.table_names.dynamic_menus', 'dynamic_menus') . ',id',
                'menus.*.sort_order' => 'nullable|integer|min:0',
                'menus.*.is_active' => 'nullable|boolean',
                'menus.*.is_visible' => 'nullable|boolean',
                'menus.*.description' => 'nullable|string',
                'menus.*.metadata' => 'nullable|array',
                'menus.*.permissions' => 'nullable|array',
                'menus.*.permissions.*' => 'exists:permissions,id',
                'menus.*.roles' => 'nullable|array',
                'menus.*.roles.*' => 'exists:roles,id',
            ]);

            // Check for unique names within the batch
            $menuNames = array_column($validated['menus'], 'name');
            $duplicateNames = array_diff_key($menuNames, array_unique($menuNames));

            if (!empty($duplicateNames)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Duplicate menu names found in the request',
                    'errors' => ['menus' => 'Menu names must be unique within the batch']
                ], 422);
            }

            // Check for existing menu names in database
            $existingNames = DynamicMenu::whereIn('name', $menuNames)->pluck('name')->toArray();
            if (!empty($existingNames)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some menu names already exist in the database',
                    'errors' => [
                        'menus' => 'The following menu names already exist: ' . implode(', ', $existingNames)
                    ]
                ], 422);
            }

            $result = $this->menuService->createMultipleMenus($validated['menus']);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['created_menus'],
                    'meta' => [
                        'total_created' => $result['total_created'],
                        'total_requested' => count($validated['menus'])
                    ],
                    'message' => "Successfully created {$result['total_created']} menus"
                ], 201);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Failed to create menus',
                    'errors' => $result['errors'],
                    'meta' => [
                        'total_created' => $result['total_created'],
                        'total_requested' => count($validated['menus'])
                    ]
                ], 422);
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create menus: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific menu
     */
    public function show(DynamicMenu $menu): JsonResponse
    {
        try {
            $menu->load(['permissions', 'roles', 'parent', 'children']);

            return response()->json([
                'success' => true,
                'data' => $menu,
                'message' => 'Menu retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve menu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a menu
     */
    public function update(Request $request, DynamicMenu $menu): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255|unique:' . config('dynamic-roles.table_names.dynamic_menus', 'dynamic_menus') . ',name,' . $menu->id,
                'label' => 'sometimes|string|max:255',
                'url' => 'nullable|string|max:500',
                'icon' => 'nullable|string|max:255',
                'route_name' => 'nullable|string|max:255',
                'route_params' => 'nullable|array',
                'parent_id' => 'nullable|exists:' . config('dynamic-roles.table_names.dynamic_menus', 'dynamic_menus') . ',id',
                'sort_order' => 'nullable|integer|min:0',
                'is_active' => 'nullable|boolean',
                'is_visible' => 'nullable|boolean',
                'description' => 'nullable|string',
                'metadata' => 'nullable|array',
                'permissions' => 'nullable|array',
                'permissions.*' => 'exists:permissions,id',
                'roles' => 'nullable|array',
                'roles.*' => 'exists:roles,id',
            ]);

            $updatedMenu = $this->menuService->updateMenu($menu, $validated);

            return response()->json([
                'success' => true,
                'data' => $updatedMenu,
                'message' => 'Menu updated successfully'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update menu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a menu
     */
    public function destroy(Request $request, DynamicMenu $menu): JsonResponse
    {
        try {
            $deleteChildren = $request->get('delete_children', true);

            $this->menuService->deleteMenu($menu, $deleteChildren);

            return response()->json([
                'success' => true,
                'message' => 'Menu deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete menu: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get menu breadcrumbs
     */
    public function breadcrumbs(DynamicMenu $menu): JsonResponse
    {
        try {
            $breadcrumbs = $this->menuService->getMenuBreadcrumbs($menu);

            return response()->json([
                'success' => true,
                'data' => $breadcrumbs,
                'message' => 'Breadcrumbs retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve breadcrumbs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reorder menus
     */
    public function reorder(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'menu_order' => 'required|array',
                'menu_order.*' => 'exists:' . config('dynamic-roles.table_names.dynamic_menus', 'dynamic_menus') . ',id',
            ]);

            $this->menuService->reorderMenus($validated['menu_order']);

            return response()->json([
                'success' => true,
                'message' => 'Menus reordered successfully'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder menus: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign permissions to menu
     */
    public function assignPermissions(Request $request, DynamicMenu $menu): JsonResponse
    {
        try {
            $validated = $request->validate([
                'permission_ids' => 'required|array',
                'permission_ids.*' => 'exists:permissions,id',
            ]);

            $this->menuService->assignPermissionsToMenu($menu, $validated['permission_ids']);

            return response()->json([
                'success' => true,
                'message' => 'Permissions assigned successfully'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign permissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign roles to menu
     */
    public function assignRoles(Request $request, DynamicMenu $menu): JsonResponse
    {
        try {
            $validated = $request->validate([
                'role_ids' => 'required|array',
                'role_ids.*' => 'exists:roles,id',
            ]);

            $this->menuService->assignRolesToMenu($menu, $validated['role_ids']);

            return response()->json([
                'success' => true,
                'message' => 'Roles assigned successfully'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign roles: ' . $e->getMessage()
            ], 500);
        }
    }
}
