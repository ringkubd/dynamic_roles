<?php

use Illuminate\Support\Facades\Route;
use Anwar\DynamicRoles\Http\Controllers\UrlPermissionController;
use Anwar\DynamicRoles\Http\Controllers\RolePermissionController;
use Anwar\DynamicRoles\Http\Controllers\MenuController;

/*
|--------------------------------------------------------------------------
| Dynamic Roles API Routes
|--------------------------------------------------------------------------
|
| These routes provide API endpoints for managing dynamic roles and
| permissions. All routes are protected by authentication middleware.
|
*/

$prefix = config('dynamic-roles.api.route_prefix', 'api/dynamic-roles');
$middleware = config('dynamic-roles.api.middleware', ['api', 'auth:sanctum']);

Route::prefix($prefix)
    ->middleware($middleware)
    ->group(function () {
        
        /*
        |--------------------------------------------------------------------------
        | URL Permission Management Routes
        |--------------------------------------------------------------------------
        */
        Route::prefix('urls')->group(function () {
            Route::get('/', [UrlPermissionController::class, 'index'])
                ->name('dynamic-roles.urls.index');
            
            Route::post('/', [UrlPermissionController::class, 'store'])
                ->name('dynamic-roles.urls.store');
            
            Route::get('/{id}', [UrlPermissionController::class, 'show'])
                ->name('dynamic-roles.urls.show');
            
            Route::put('/{id}', [UrlPermissionController::class, 'update'])
                ->name('dynamic-roles.urls.update');
            
            Route::delete('/{id}', [UrlPermissionController::class, 'destroy'])
                ->name('dynamic-roles.urls.destroy');
            
            Route::post('/check-permission', [UrlPermissionController::class, 'checkPermission'])
                ->name('dynamic-roles.urls.check-permission');
            
            Route::post('/auto-discover', [UrlPermissionController::class, 'autoDiscover'])
                ->name('dynamic-roles.urls.auto-discover');
            
            Route::patch('/bulk-update', [UrlPermissionController::class, 'bulkUpdate'])
                ->name('dynamic-roles.urls.bulk-update');
        });

        /*
        |--------------------------------------------------------------------------
        | Role & Permission Management Routes
        |--------------------------------------------------------------------------
        */
        Route::prefix('roles')->group(function () {
            Route::get('/', [RolePermissionController::class, 'roles'])
                ->name('dynamic-roles.roles.index');
            
            Route::post('/', [RolePermissionController::class, 'createRole'])
                ->name('dynamic-roles.roles.store');
            
            Route::post('/{roleId}/permissions', [RolePermissionController::class, 'assignPermissions'])
                ->name('dynamic-roles.roles.assign-permissions');
            
            Route::delete('/{roleId}/permissions', [RolePermissionController::class, 'removePermissions'])
                ->name('dynamic-roles.roles.remove-permissions');
            
            Route::get('/{roleId}/permissions', [RolePermissionController::class, 'rolePermissions'])
                ->name('dynamic-roles.roles.permissions');
        });

        Route::prefix('permissions')->group(function () {
            Route::get('/', [RolePermissionController::class, 'permissions'])
                ->name('dynamic-roles.permissions.index');
            
            Route::post('/', [RolePermissionController::class, 'createPermission'])
                ->name('dynamic-roles.permissions.store');
        });

        /*
        |--------------------------------------------------------------------------
        | User Role Management Routes
        |--------------------------------------------------------------------------
        */
        Route::prefix('users')->group(function () {
            Route::post('/assign-role', [RolePermissionController::class, 'assignRole'])
                ->name('dynamic-roles.users.assign-role');
            
            Route::post('/remove-role', [RolePermissionController::class, 'removeRole'])
                ->name('dynamic-roles.users.remove-role');
            
            Route::get('/{userId}/permissions', [RolePermissionController::class, 'userPermissions'])
                ->name('dynamic-roles.users.permissions');
        });

        /*
        |--------------------------------------------------------------------------
        | Bulk Operations Routes
        |--------------------------------------------------------------------------
        */
        Route::prefix('bulk')->group(function () {
            Route::post('/assign-permissions', [RolePermissionController::class, 'bulkAssignPermissions'])
                ->name('dynamic-roles.bulk.assign-permissions');
        });

        /*
        |--------------------------------------------------------------------------
        | Statistics & Management Routes
        |--------------------------------------------------------------------------
        */
        Route::get('/stats', [RolePermissionController::class, 'stats'])
            ->name('dynamic-roles.stats');
        
        Route::get('/export', [RolePermissionController::class, 'export'])
            ->name('dynamic-roles.export');
        
        Route::post('/import', [RolePermissionController::class, 'import'])
            ->name('dynamic-roles.import');
        
        /*
        |--------------------------------------------------------------------------
        | Menu Management Routes
        |--------------------------------------------------------------------------
        */
        Route::prefix('menus')->group(function () {
            Route::get('/', [MenuController::class, 'index'])
                ->name('dynamic-roles.menus.index');
            
            Route::get('/tree', [MenuController::class, 'tree'])
                ->name('dynamic-roles.menus.tree');
            
            Route::post('/', [MenuController::class, 'store'])
                ->name('dynamic-roles.menus.store');
            
            Route::get('/{menu}', [MenuController::class, 'show'])
                ->name('dynamic-roles.menus.show');
            
            Route::put('/{menu}', [MenuController::class, 'update'])
                ->name('dynamic-roles.menus.update');
            
            Route::delete('/{menu}', [MenuController::class, 'destroy'])
                ->name('dynamic-roles.menus.destroy');
            
            Route::get('/{menu}/breadcrumbs', [MenuController::class, 'breadcrumbs'])
                ->name('dynamic-roles.menus.breadcrumbs');
            
            Route::post('/reorder', [MenuController::class, 'reorder'])
                ->name('dynamic-roles.menus.reorder');
            
            Route::post('/{menu}/assign-permissions', [MenuController::class, 'assignPermissions'])
                ->name('dynamic-roles.menus.assign-permissions');
            
            Route::post('/{menu}/assign-roles', [MenuController::class, 'assignRoles'])
                ->name('dynamic-roles.menus.assign-roles');
        });
    });

/*
|--------------------------------------------------------------------------
| Public Routes (No Authentication Required)
|--------------------------------------------------------------------------
|
| These routes are for checking permissions without authentication
| Useful for frontend applications to check if a user would have access
|
*/
Route::prefix($prefix . '/public')
    ->middleware(['api'])
    ->group(function () {
        Route::post('/check-url-access', function (Illuminate\Http\Request $request) {
            $validated = $request->validate([
                'url' => 'required|string',
                'method' => 'required|string|in:GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS',
                'user_id' => 'nullable|integer',
            ]);

            $urlPermissionService = app(\Ringkubd\DynamicRoles\Services\UrlPermissionService::class);
            
            if ($validated['user_id']) {
                $userModel = config('auth.providers.users.model');
                $user = $userModel::find($validated['user_id']);
                $hasAccess = $urlPermissionService->checkUrlPermission(
                    $user, 
                    $validated['url'], 
                    $validated['method']
                );
            } else {
                $hasAccess = false; // No user provided
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'has_access' => $hasAccess,
                    'url' => $validated['url'],
                    'method' => $validated['method'],
                    'user_id' => $validated['user_id'] ?? null,
                ],
                'message' => 'Access check completed'
            ]);
        })->name('dynamic-roles.public.check-url-access');

        Route::get('/url-patterns', function () {
            $urls = \Ringkubd\DynamicRoles\Models\DynamicUrl::where('is_active', true)
                ->select(['url', 'method', 'name', 'category'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $urls,
                'message' => 'URL patterns retrieved successfully'
            ]);
        })->name('dynamic-roles.public.url-patterns');
    });
