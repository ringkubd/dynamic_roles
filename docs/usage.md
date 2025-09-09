# Usage Examples

This document provides practical examples of how to use the Dynamic Roles package in your Laravel application.

## Basic Setup

After installation, here's how to get started:

### 1. Create Your First Permission

```php
// Using the API
POST /api/dynamic-roles/url-permissions
{
    "url": "/admin/users",
    "method": "GET",
    "description": "View users list",
    "is_active": true
}

// Or using the service directly
use Anwar\DynamicRoles\Services\UrlPermissionService;

$service = app(UrlPermissionService::class);
$permission = $service->createPermission([
    'url' => '/admin/users',
    'method' => 'GET',
    'description' => 'View users list',
    'is_active' => true
]);
```

### 2. Assign Permission to Role

```php
// Using the API
POST /api/dynamic-roles/role-permissions
{
    "role_id": 1,
    "permission_ids": [1, 2, 3]
}

// Or using the service
use Anwar\DynamicRoles\Services\RolePermissionService;

$service = app(RolePermissionService::class);
$service->assignPermissions(1, [1, 2, 3]);
```

### 3. Check Permissions in Your Controllers

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Anwar\DynamicRoles\Services\UrlPermissionService;

class AdminController extends Controller
{
    protected $permissionService;

    public function __construct(UrlPermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function users(Request $request)
    {
        // Manual permission check
        if (!$this->permissionService->checkUrlPermission($request->user(), '/admin/users', 'GET')) {
            abort(403, 'Access denied');
        }

        return view('admin.users');
    }
}
```

### 4. Using Middleware Protection

```php
// In your routes/web.php
Route::middleware(['dynamic-permission'])->group(function () {
    Route::get('/admin/users', [AdminController::class, 'users']);
    Route::post('/admin/users', [AdminController::class, 'store']);
    Route::put('/admin/users/{user}', [AdminController::class, 'update']);
});

// Or protect specific routes
Route::get('/admin/settings', [AdminController::class, 'settings'])
    ->middleware('dynamic-permission');
```

## Menu Management Examples

### 1. Creating Menu Structure

```php
use Anwar\DynamicRoles\Services\MenuService;

$menuService = app(MenuService::class);

// Create main menu
$mainMenu = $menuService->createMenu([
    'title' => 'Dashboard',
    'url' => '/dashboard',
    'icon' => 'dashboard',
    'order' => 1,
    'is_active' => true
]);

// Create submenu
$userMenu = $menuService->createMenu([
    'title' => 'User Management',
    'url' => '/admin/users',
    'icon' => 'users',
    'parent_id' => $mainMenu->id,
    'order' => 1,
    'is_active' => true
]);
```

### 2. Get Menu Tree for Current User

```php
// In your controller
public function getMenuForUser(Request $request)
{
    $menuService = app(MenuService::class);
    $menuTree = $menuService->getMenuTreeForUser($request->user());
    
    return response()->json($menuTree);
}
```

### 3. Frontend Integration (React/Vue Example)

```javascript
// Fetch user menu
const fetchUserMenu = async () => {
    try {
        const response = await fetch('/api/dynamic-roles/menus/tree', {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });
        
        const menuData = await response.json();
        setMenuItems(menuData);
    } catch (error) {
        console.error('Failed to fetch menu:', error);
    }
};

// Render menu component
const MenuItem = ({ item, level = 0 }) => (
    <div style={{ paddingLeft: level * 20 }}>
        <Link to={item.url}>
            {item.icon && <Icon name={item.icon} />}
            {item.title}
        </Link>
        {item.children && item.children.map(child => (
            <MenuItem key={child.id} item={child} level={level + 1} />
        ))}
    </div>
);
```

### 4. Blade Template Integration

```blade
{{-- resources/views/layouts/sidebar.blade.php --}}
@inject('menuService', 'Anwar\DynamicRoles\Services\MenuService')

<nav class="sidebar">
    @foreach($menuService->getMenuTreeForUser(auth()->user()) as $menuItem)
        <div class="menu-item">
            <a href="{{ $menuItem['url'] }}" class="menu-link">
                @if($menuItem['icon'])
                    <i class="icon {{ $menuItem['icon'] }}"></i>
                @endif
                {{ $menuItem['title'] }}
            </a>
            
            @if(!empty($menuItem['children']))
                <div class="submenu">
                    @foreach($menuItem['children'] as $subItem)
                        <a href="{{ $subItem['url'] }}" class="submenu-link">
                            {{ $subItem['title'] }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach
</nav>
```

## Advanced Examples

### 1. Custom Permission Logic

```php
<?php

namespace App\Services;

use Anwar\DynamicRoles\Services\UrlPermissionService;

class CustomPermissionService extends UrlPermissionService
{
    public function checkCustomPermission($user, $resource, $action)
    {
        // Add your custom logic here
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Check resource-specific permissions
        return $this->checkUrlPermission($user, "/{$resource}", strtoupper($action));
    }
}
```

### 2. Bulk Permission Management

```php
// Assign multiple permissions to multiple roles
$rolePermissionService = app(RolePermissionService::class);

$bulkAssignments = [
    ['role_id' => 1, 'permission_ids' => [1, 2, 3]],
    ['role_id' => 2, 'permission_ids' => [2, 3, 4]],
    ['role_id' => 3, 'permission_ids' => [1, 4, 5]],
];

foreach ($bulkAssignments as $assignment) {
    $rolePermissionService->assignPermissions(
        $assignment['role_id'], 
        $assignment['permission_ids']
    );
}
```

### 3. Cache Management

```php
use Anwar\DynamicRoles\Services\PermissionCacheService;

$cacheService = app(PermissionCacheService::class);

// Clear all permission cache
$cacheService->clearAllCache();

// Clear cache for specific user
$cacheService->clearUserCache($userId);

// Clear menu cache
$cacheService->clearByTag(['menus']);
```

### 4. Import/Export Permissions

```php
// Export current permissions
$urlPermissionService = app(UrlPermissionService::class);
$permissions = $urlPermissionService->exportPermissions();

// Save to file
file_put_contents('permissions_backup.json', json_encode($permissions, JSON_PRETTY_PRINT));

// Import permissions
$permissionsData = json_decode(file_get_contents('permissions_backup.json'), true);
$urlPermissionService->importPermissions($permissionsData);
```

## Configuration Examples

### Custom Table Names

```php
// config/dynamic-roles.php
return [
    'database' => [
        'tables' => [
            'dynamic_urls' => 'custom_urls',
            'dynamic_url_permissions' => 'custom_url_permissions',
            'dynamic_role_urls' => 'custom_role_urls',
            'dynamic_permission_checks' => 'custom_permission_checks',
            'dynamic_menus' => 'custom_menus',
            'dynamic_menu_permissions' => 'custom_menu_permissions',
            'dynamic_menu_roles' => 'custom_menu_roles',
        ]
    ],
    // ... other config
];
```

### Custom Cache Configuration

```php
// config/dynamic-roles.php
return [
    'cache' => [
        'enabled' => true,
        'driver' => 'redis',
        'ttl' => 3600, // 1 hour
        'prefix' => 'my_app_permissions:',
        'tags' => [
            'permissions' => 'my_app_perms',
            'roles' => 'my_app_roles',
            'menus' => 'my_app_menus'
        ]
    ]
];
```

## Testing Examples

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DynamicRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_permitted_url()
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'editor']);
        $user->assignRole($role);

        // Create permission
        $response = $this->postJson('/api/dynamic-roles/url-permissions', [
            'url' => '/admin/posts',
            'method' => 'GET',
            'description' => 'View posts',
            'is_active' => true
        ]);

        $permission = $response->json();

        // Assign to role
        $this->postJson('/api/dynamic-roles/role-permissions', [
            'role_id' => $role->id,
            'permission_ids' => [$permission['id']]
        ]);

        // Test access
        $this->actingAs($user)
             ->get('/admin/posts')
             ->assertStatus(200);
    }
}
```

This package provides a complete solution for dynamic permission and menu management in Laravel applications. The examples above should help you get started and implement advanced features as needed.
