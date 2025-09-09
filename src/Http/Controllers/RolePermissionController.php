<?php

namespace Anwar\DynamicRoles\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Gunma\DynamicRoles\Services\RolePermissionService;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Validation\ValidationException;

class RolePermissionController extends Controller
{
    protected RolePermissionService $rolePermissionService;

    public function __construct(RolePermissionService $rolePermissionService)
    {
        $this->rolePermissionService = $rolePermissionService;
    }

    /**
     * Get all roles with their permissions.
     */
    public function roles(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['search', 'guard_name']);
            $roles = $this->rolePermissionService->getAllRoles($filters);

            return response()->json([
                'success' => true,
                'data' => $roles,
                'message' => 'Roles retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve roles: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all permissions.
     */
    public function permissions(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['search', 'guard_name']);
            $permissions = $this->rolePermissionService->getAllPermissions($filters);

            return response()->json([
                'success' => true,
                'data' => $permissions,
                'message' => 'Permissions retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve permissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new role.
     */
    public function createRole(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|unique:roles,name|max:255',
                'guard_name' => 'string|max:255',
                'permissions' => 'array',
                'permissions.*' => 'string|exists:permissions,name',
            ]);

            $permissions = $validated['permissions'] ?? [];
            unset($validated['permissions']);

            $role = $this->rolePermissionService->createRole(
                $validated['name'],
                $permissions,
                $validated
            );

            return response()->json([
                'success' => true,
                'data' => $role->load('permissions'),
                'message' => 'Role created successfully'
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
                'message' => 'Failed to create role: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new permission.
     */
    public function createPermission(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|unique:permissions,name|max:255',
                'guard_name' => 'string|max:255',
            ]);

            $permission = $this->rolePermissionService->createPermission(
                $validated['name'],
                $validated
            );

            return response()->json([
                'success' => true,
                'data' => $permission,
                'message' => 'Permission created successfully'
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
                'message' => 'Failed to create permission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign permissions to a role.
     */
    public function assignPermissions(Request $request, int $roleId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'permissions' => 'required|array',
                'permissions.*' => 'string|exists:permissions,name',
            ]);

            $role = Role::findOrFail($roleId);
            $this->rolePermissionService->assignPermissionsToRole($role, $validated['permissions']);

            return response()->json([
                'success' => true,
                'data' => $role->load('permissions'),
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
     * Remove permissions from a role.
     */
    public function removePermissions(Request $request, int $roleId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'permissions' => 'required|array',
                'permissions.*' => 'string|exists:permissions,name',
            ]);

            $role = Role::findOrFail($roleId);
            $this->rolePermissionService->removePermissionsFromRole($role, $validated['permissions']);

            return response()->json([
                'success' => true,
                'data' => $role->load('permissions'),
                'message' => 'Permissions removed successfully'
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
                'message' => 'Failed to remove permissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign role to a user.
     */
    public function assignRole(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'role' => 'required|string|exists:roles,name',
            ]);

            $userModel = config('auth.providers.users.model');
            $user = $userModel::findOrFail($validated['user_id']);
            
            $this->rolePermissionService->assignRoleToUser($user, $validated['role']);

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                    'role' => $validated['role'],
                    'user_roles' => $user->roles->pluck('name'),
                ],
                'message' => 'Role assigned successfully'
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
                'message' => 'Failed to assign role: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove role from a user.
     */
    public function removeRole(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'role' => 'required|string|exists:roles,name',
            ]);

            $userModel = config('auth.providers.users.model');
            $user = $userModel::findOrFail($validated['user_id']);
            
            $this->rolePermissionService->removeRoleFromUser($user, $validated['role']);

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                    'role' => $validated['role'],
                    'user_roles' => $user->roles->pluck('name'),
                ],
                'message' => 'Role removed successfully'
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
                'message' => 'Failed to remove role: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user permissions.
     */
    public function userPermissions(Request $request, int $userId): JsonResponse
    {
        try {
            $userModel = config('auth.providers.users.model');
            $user = $userModel::findOrFail($userId);
            
            $permissions = $this->rolePermissionService->getUserPermissions($user);

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $userId,
                    'permissions' => $permissions,
                    'roles' => $user->roles->pluck('name'),
                ],
                'message' => 'User permissions retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user permissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get role permissions.
     */
    public function rolePermissions(int $roleId): JsonResponse
    {
        try {
            $role = Role::findOrFail($roleId);
            $permissions = $this->rolePermissionService->getRolePermissions($role);

            return response()->json([
                'success' => true,
                'data' => [
                    'role_id' => $roleId,
                    'role_name' => $role->name,
                    'permissions' => $permissions,
                ],
                'message' => 'Role permissions retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve role permissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk assign permissions to roles.
     */
    public function bulkAssignPermissions(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'role_permissions' => 'required|array',
                'role_permissions.*' => 'array',
            ]);

            $this->rolePermissionService->bulkAssignPermissions($validated['role_permissions']);

            return response()->json([
                'success' => true,
                'message' => 'Permissions assigned successfully to all roles'
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
                'message' => 'Failed to bulk assign permissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get permission statistics.
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->rolePermissionService->getPermissionStats();

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Permission statistics retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export configuration.
     */
    public function export(): JsonResponse
    {
        try {
            $config = $this->rolePermissionService->exportConfiguration();

            return response()->json([
                'success' => true,
                'data' => $config,
                'message' => 'Configuration exported successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export configuration: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import configuration.
     */
    public function import(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'config' => 'required|array',
                'config.roles' => 'array',
                'config.urls' => 'array',
            ]);

            $this->rolePermissionService->importConfiguration($validated['config']);

            return response()->json([
                'success' => true,
                'message' => 'Configuration imported successfully'
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
                'message' => 'Failed to import configuration: ' . $e->getMessage()
            ], 500);
        }
    }
}
