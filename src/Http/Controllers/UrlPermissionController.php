<?php

namespace Anwar\DynamicRoles\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Anwar\DynamicRoles\Services\UrlPermissionService;
use Anwar\DynamicRoles\Models\DynamicUrl;
use Illuminate\Validation\ValidationException;

class UrlPermissionController extends Controller
{
    protected UrlPermissionService $urlPermissionService;

    public function __construct(UrlPermissionService $urlPermissionService)
    {
        $this->urlPermissionService = $urlPermissionService;
    }

    /**
     * Get all URLs with pagination and filtering.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['method', 'category', 'is_active', 'search', 'per_page']);
            $urls = $this->urlPermissionService->getAllUrls($filters);

            return response()->json([
                'success' => true,
                'data' => $urls,
                'message' => 'URLs retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve URLs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific URL with its permissions and roles.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $url = DynamicUrl::with(['permissions', 'roles'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $url,
                'message' => 'URL retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'URL not found'
            ], 404);
        }
    }

    /**
     * Create a new URL with permissions.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'url' => 'required|string|max:255',
                'method' => 'required|string|in:GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS',
                'name' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'category' => 'nullable|string|max:100',
                'is_active' => 'boolean',
                'permissions' => 'array',
                'permissions.*' => 'string',
                'roles' => 'array',
                'roles.*' => 'string',
                'metadata' => 'array',
            ]);

            $permissions = $validated['permissions'] ?? [];
            $roles = $validated['roles'] ?? [];
            unset($validated['permissions'], $validated['roles']);

            $url = $this->urlPermissionService->registerUrl(
                $validated['url'],
                $validated['method'],
                $permissions,
                $validated
            );

            if (!empty($roles)) {
                $this->urlPermissionService->assignRolesToUrl($url, $roles);
            }

            return response()->json([
                'success' => true,
                'data' => $url->load(['permissions', 'roles']),
                'message' => 'URL created successfully'
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
                'message' => 'Failed to create URL: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing URL.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $url = DynamicUrl::findOrFail($id);

            $validated = $request->validate([
                'url' => 'string|max:255',
                'method' => 'string|in:GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS',
                'name' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'category' => 'nullable|string|max:100',
                'is_active' => 'boolean',
                'permissions' => 'array',
                'permissions.*' => 'string',
                'roles' => 'array',
                'roles.*' => 'string',
                'metadata' => 'array',
            ]);

            $permissions = $validated['permissions'] ?? null;
            $roles = $validated['roles'] ?? null;
            unset($validated['permissions'], $validated['roles']);

            $url->update($validated);

            if ($permissions !== null) {
                $this->urlPermissionService->assignPermissionsToUrl($url, $permissions);
            }

            if ($roles !== null) {
                $this->urlPermissionService->assignRolesToUrl($url, $roles);
            }

            return response()->json([
                'success' => true,
                'data' => $url->load(['permissions', 'roles']),
                'message' => 'URL updated successfully'
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
                'message' => 'Failed to update URL: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a URL.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->urlPermissionService->deleteUrl($id);

            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'URL deleted successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete URL'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'URL not found'
            ], 404);
        }
    }

    /**
     * Check if current user has permission for a URL.
     */
    public function checkPermission(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'url' => 'required|string',
                'method' => 'required|string|in:GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS',
            ]);

            $user = $request->user();
            $hasPermission = $this->urlPermissionService->checkUrlPermission(
                $user,
                $validated['url'],
                $validated['method']
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'has_permission' => $hasPermission,
                    'url' => $validated['url'],
                    'method' => $validated['method'],
                    'user_id' => $user?->id,
                ],
                'message' => 'Permission check completed'
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
                'message' => 'Failed to check permission: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Auto-discover routes and register them.
     */
    public function autoDiscover(): JsonResponse
    {
        try {
            $discovered = $this->urlPermissionService->autoDiscoverRoutes();

            return response()->json([
                'success' => true,
                'data' => [
                    'discovered_count' => count($discovered),
                    'urls' => $discovered
                ],
                'message' => 'Routes auto-discovered successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to auto-discover routes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update URLs.
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'urls' => 'required|array',
                'urls.*.id' => 'required|integer|exists:dynamic_urls,id',
                'urls.*.is_active' => 'boolean',
                'urls.*.category' => 'nullable|string|max:100',
                'urls.*.permissions' => 'array',
                'urls.*.permissions.*' => 'string',
                'urls.*.roles' => 'array',
                'urls.*.roles.*' => 'string',
            ]);

            $updated = [];
            
            foreach ($validated['urls'] as $urlData) {
                $url = DynamicUrl::findOrFail($urlData['id']);
                
                $updateData = array_intersect_key($urlData, [
                    'is_active' => true,
                    'category' => true,
                ]);
                
                if (!empty($updateData)) {
                    $url->update($updateData);
                }

                if (isset($urlData['permissions'])) {
                    $this->urlPermissionService->assignPermissionsToUrl($url, $urlData['permissions']);
                }

                if (isset($urlData['roles'])) {
                    $this->urlPermissionService->assignRolesToUrl($url, $urlData['roles']);
                }

                $updated[] = $url->load(['permissions', 'roles']);
            }

            return response()->json([
                'success' => true,
                'data' => $updated,
                'message' => 'URLs updated successfully'
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
                'message' => 'Failed to bulk update URLs: ' . $e->getMessage()
            ], 500);
        }
    }
}
