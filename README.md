# Dynamic Roles Package

A comprehensive Laravel package for dynamic role and permission management with caching, API support, and complete database-driven URL management. Perfect for applications with complex permission requirements that need to be managed without touching code.

## Features

- 🚀 **Dynamic Permission Management**: Create and manage permissions without touching code
- 🎯 **URL-Based Access Control**: Control access to specific URLs and HTTP methods
- ⚡ **High Performance Caching**: Configurable caching with Redis, Memcached, or other drivers
- 🔧 **Auto-Discovery**: Automatically discover and register routes from your application
- 🌐 **Complete API**: Full REST API for frontend integration (perfect for Next.js)
- 🛡️ **Middleware Protection**: Ready-to-use middleware for route protection
- 📊 **Analytics & Logging**: Track permission checks and access patterns
- 🎨 **Flexible Configuration**: Highly configurable to fit any application structure
- 💾 **Import/Export**: Backup and restore permission configurations
- 🔄 **Bulk Operations**: Efficiently manage permissions for multiple entities
- 🍔 **Menu Management**: Create hierarchical menu systems with role/permission-based access
- 🧭 **Breadcrumb Support**: Automatic breadcrumb generation for nested menus
- 🏗️ **Tree Structure**: Build complex nested menu structures with unlimited depth

## Installation

### 1. Install the Package

Run on terminal:

```bash
composer require anwar/dynamic-roles
```
Or add it on your main `composer.json`

Then run:

```bash
composer install
```

### 2. Install Spatie Laravel Permission (Required Dependency)

```bash
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

### 3. Publish and Run Migrations

```bash
# Publish configuration
php artisan vendor:publish --tag=dynamic-roles-config

# Publish migrations
php artisan vendor:publish --tag=dynamic-roles-migrations

# Run migrations
php artisan migrate
```

### 4. Add Service Provider (If not auto-discovered)

Add to `config/app.php`:

```php
'providers' => [
    // ...
    Anwar\DynamicRoles\DynamicRolesServiceProvider::class,
],
```

### 5. Add Traits to User Model

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;
    
    // ... your existing code
}
```

## Configuration

### Environment Variables

Add these to your `.env` file:

```env
# Cache Configuration
DYNAMIC_ROLES_CACHE_ENABLED=true
DYNAMIC_ROLES_CACHE_DRIVER=redis
DYNAMIC_ROLES_CACHE_PREFIX=dynamic_roles
DYNAMIC_ROLES_CACHE_TTL=3600

# API Configuration
DYNAMIC_ROLES_ENABLE_API=true
DYNAMIC_ROLES_API_PREFIX=api/dynamic-roles

# Auto-Discovery
DYNAMIC_ROLES_AUTO_DISCOVERY=true
DYNAMIC_ROLES_AUTO_REGISTER_URLS=true

# Security
DYNAMIC_ROLES_SUPER_ADMIN=super-admin
DYNAMIC_ROLES_BYPASS_PERMISSIONS=false
DYNAMIC_ROLES_LOG_CHECKS=false
```

### Configuration File

The package publishes a configuration file at `config/dynamic-roles.php`. Key sections include:

- **Cache Settings**: Configure caching behavior and drivers
- **Database Settings**: Customize table names and connections
- **API Settings**: Configure API routes and middleware
- **Security Settings**: Set super admin roles and security options
- **Discovery Settings**: Configure auto-discovery patterns

## Usage

### Basic Usage

#### 1. Register URLs with Permissions

```php
use Anwar\DynamicRoles\Facades\DynamicRoles;

// Register a URL with specific permissions
DynamicRoles::registerUrl(
    '/api/users',
    'GET',
    ['users.view', 'users.list'],
    [
        'name' => 'users.index',
        'description' => 'List all users',
        'category' => 'api'
    ]
);

// Auto-discover all routes
DynamicRoles::autoDiscoverRoutes();
```

#### 2. Use Middleware

```php
// In your routes/api.php
Route::middleware(['dynamic.permission'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
});

// With specific permissions
Route::get('/admin/users', [UserController::class, 'adminIndex'])
    ->middleware('dynamic.permission:admin.users.view');

// With role-based access
Route::middleware(['dynamic.role:admin,manager'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
});
```

#### 3. Programmatic Permission Checks

```php
use Anwar\DynamicRoles\Services\UrlPermissionService;

$urlPermissionService = app(UrlPermissionService::class);

// Check if user has permission for a specific URL
$hasPermission = $urlPermissionService->checkUrlPermission(
    $user,
    '/api/users',
    'GET'
);

if ($hasPermission) {
    // Allow access
} else {
    // Deny access
}
```

### API Usage

The package provides a complete REST API for managing roles and permissions:

#### Authentication

All API endpoints require authentication. Configure the middleware in the config file:

```php
'api' => [
    'middleware' => [
        'api',
        'auth:sanctum', // or 'auth:api', 'jwt.auth', etc.
    ],
],
```

#### API Endpoints

**URL Management:**
```bash
# Get all URLs
GET /api/dynamic-roles/urls

# Create new URL
POST /api/dynamic-roles/urls
{
    "url": "/api/products",
    "method": "GET",
    "permissions": ["products.view"],
    "roles": ["user", "admin"]
}

# Update URL
PUT /api/dynamic-roles/urls/{id}

# Delete URL
DELETE /api/dynamic-roles/urls/{id}

# Check permission
POST /api/dynamic-roles/urls/check-permission
{
    "url": "/api/products",
    "method": "GET"
}

# Auto-discover routes
POST /api/dynamic-roles/urls/auto-discover
```

**Role Management:**
```bash
# Get all roles
GET /api/dynamic-roles/roles

# Create role
POST /api/dynamic-roles/roles
{
    "name": "editor",
    "permissions": ["posts.create", "posts.edit"]
}

# Assign permissions to role
POST /api/dynamic-roles/roles/{roleId}/permissions
{
    "permissions": ["posts.create", "posts.edit", "posts.delete"]
}

# Get role permissions
GET /api/dynamic-roles/roles/{roleId}/permissions
```

**User Management:**
```bash
# Assign role to user
POST /api/dynamic-roles/users/assign-role
{
    "user_id": 1,
    "role": "editor"
}

# Get user permissions
GET /api/dynamic-roles/users/{userId}/permissions
```

#### Frontend Integration (Next.js Example)

```typescript
// lib/permissions.ts
interface PermissionCheck {
  url: string;
  method: string;
}

export async function checkPermission({ url, method }: PermissionCheck): Promise<boolean> {
  try {
    const response = await fetch('/api/dynamic-roles/urls/check-permission', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${getAuthToken()}`,
      },
      body: JSON.stringify({ url, method }),
    });
    
    const result = await response.json();
    return result.data.has_permission;
  } catch (error) {
    console.error('Permission check failed:', error);
    return false;
  }
}

// Usage in React component
import { checkPermission } from '@/lib/permissions';

export default function ProductList() {
  const [canEdit, setCanEdit] = useState(false);
  
  useEffect(() => {
    checkPermission({ url: '/api/products', method: 'PUT' })
      .then(setCanEdit);
  }, []);
  
  return (
    <div>
      {canEdit && <button>Edit Product</button>}
    </div>
  );
}
```

### Advanced Features

#### 1. Custom Permission Patterns

Configure auto-discovery patterns in `config/dynamic-roles.php`:

```php
'discovery' => [
    'permission_patterns' => [
        'create' => ['store', 'create', 'add'],
        'read' => ['index', 'show', 'view', 'list'],
        'update' => ['update', 'edit', 'modify'],
        'delete' => ['destroy', 'delete', 'remove'],
        'admin' => ['admin', 'manage'],
    ],
],
```

#### 2. Cache Configuration

```php
'cache' => [
    'enabled' => true,
    'driver' => 'redis', // redis, memcached, file, etc.
    'ttl' => 3600,
    'tags' => [
        'permissions' => 'dynamic_roles_permissions',
        'roles' => 'dynamic_roles_roles',
        'urls' => 'dynamic_roles_urls',
    ],
],
```

#### 3. Bulk Operations

```php
use Anwar\DynamicRoles\Services\RolePermissionService;

$rolePermissionService = app(RolePermissionService::class);

// Bulk assign permissions to roles
$rolePermissionService->bulkAssignPermissions([
    'admin' => ['users.create', 'users.edit', 'users.delete'],
    'editor' => ['posts.create', 'posts.edit'],
    'viewer' => ['posts.view'],
]);

// Bulk assign roles to users
$rolePermissionService->bulkAssignRoles([
    1 => ['admin'],
    2 => ['editor'],
    3 => ['viewer'],
]);
```

#### 4. Import/Export Configuration

```php
// Export current configuration
$config = $rolePermissionService->exportConfiguration();
file_put_contents('permissions-backup.json', json_encode($config, JSON_PRETTY_PRINT));

// Import configuration
$config = json_decode(file_get_contents('permissions-backup.json'), true);
$rolePermissionService->importConfiguration($config);
```

## Artisan Commands

The package provides several useful Artisan commands:

```bash
# Sync permissions and auto-discover routes
php artisan dynamic-roles:sync-permissions --auto-discover --clear-cache

# Clear specific caches
php artisan dynamic-roles:clear-cache --user=1
php artisan dynamic-roles:clear-cache --role=2
php artisan dynamic-roles:clear-cache --url=/api/users --method=GET

# Clear all permission cache
php artisan dynamic-roles:clear-cache

# Publish configuration (force overwrite)
php artisan dynamic-roles:publish-config
```

## Testing

Create tests for your permissions:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Anwar\DynamicRoles\Facades\DynamicRoles;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_allowed_url()
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        
        DynamicRoles::registerUrl('/api/profile', 'GET', ['profile.view']);
        
        $response = $this->actingAs($user)
            ->getJson('/api/profile');
            
        $response->assertStatus(200);
    }

    public function test_user_cannot_access_forbidden_url()
    {
        $user = User::factory()->create();
        
        DynamicRoles::registerUrl('/api/admin', 'GET', ['admin.access']);
        
        $response = $this->actingAs($user)
            ->getJson('/api/admin');
            
        $response->assertStatus(403);
    }
}
```

## Performance Optimization

### 1. Caching Strategy

The package uses intelligent caching:

- **User permissions** are cached per user
- **URL permissions** are cached per URL+method combination
- **Role permissions** are cached per role
- Cache invalidation happens automatically on changes

### 2. Database Optimization

- Proper indexing on all lookup columns
- Eager loading of relationships
- Optimized queries for permission checks

### 3. Configuration Tips

```php
// Enable preloading for better performance
'performance' => [
    'preload_permissions' => true,
    'eager_load_relationships' => true,
    'batch_permission_checks' => true,
],
```

## Security Considerations

### 1. Super Admin Role

Configure a super admin role that bypasses all permission checks:

```php
'security' => [
    'super_admin_role' => 'super-admin',
    'bypass_permissions' => false, // Never set to true in production
],
```

### 2. API Security

- Always use authentication middleware
- Consider rate limiting for API endpoints
- Validate all input data
- Log permission checks for auditing

### 3. Database Security

- Use database transactions for bulk operations
- Implement soft deletes for audit trails
- Regular backups of permission configurations

## Troubleshooting

### Common Issues

1. **Middleware not working**
   - Ensure middleware is registered in kernel
   - Check route definitions
   - Verify user authentication

2. **Cache not clearing**
   - Check cache driver configuration
   - Ensure proper Redis/Memcached connection
   - Verify cache key patterns

3. **Auto-discovery not working**
   - Check route registration timing
   - Verify excluded patterns
   - Ensure proper controller naming

### Debug Mode

Enable detailed logging:

```env
DYNAMIC_ROLES_LOG_CHECKS=true
```

Check logs at `storage/logs/laravel.log` for detailed permission check information.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).

## Support

For support, please check:

1. This documentation
2. Laravel logs (`storage/logs/laravel.log`)
3. Package configuration (`config/dynamic-roles.php`)
4. Database tables and relationships

## Changelog

### Version 1.0.0
- Initial release
- Complete role and permission management
- API endpoints for frontend integration
- Caching system with multiple driver support
- Auto-discovery functionality
- Comprehensive middleware protection
- Import/export capabilities
- Artisan commands for management
- Complete documentation and examples

## Menu Management

The package includes a complete menu management system that allows you to create hierarchical menu structures with role and permission-based access control.

### Creating Menus

```php
use Anwar\DynamicRoles\Services\MenuService;

$menuService = app(MenuService::class);

// Create a parent menu
$parentMenu = $menuService->createMenu([
    'name' => 'dashboard',
    'label' => 'Dashboard',
    'url' => '/dashboard',
    'icon' => 'fas fa-tachometer-alt',
    'is_active' => true,
    'is_visible' => true,
    'sort_order' => 1,
]);

// Create a child menu
$childMenu = $menuService->createMenu([
    'name' => 'user_management',
    'label' => 'User Management',
    'url' => '/users',
    'icon' => 'fas fa-users',
    'parent_id' => $parentMenu->id,
    'sort_order' => 1,
    'permissions' => [1, 2], // Permission IDs
    'roles' => [1], // Role IDs
]);
```

### Getting Menu Tree

```php
// Get menu tree for current user (filtered by permissions)
$userMenuTree = $menuService->getMenuTreeForUser(auth()->user());

// Get full menu tree (admin view)
$fullMenuTree = $menuService->getFullMenuTree();
```

### Menu API Endpoints

All menu endpoints are available under `/api/dynamic-roles/menus/`:

- `GET /menus` - List all menus with filtering
- `GET /menus/tree` - Get menu tree for current user
- `POST /menus` - Create a new menu
- `GET /menus/{id}` - Get specific menu
- `PUT /menus/{id}` - Update menu
- `DELETE /menus/{id}` - Delete menu
- `GET /menus/{id}/breadcrumbs` - Get breadcrumbs for menu
- `POST /menus/reorder` - Reorder menu items
- `POST /menus/{id}/assign-permissions` - Assign permissions to menu
- `POST /menus/{id}/assign-roles` - Assign roles to menu

### Menu Configuration

Configure menu behavior in `config/dynamic-roles.php`:

```php
'menu' => [
    'enabled' => true,
    'cache_enabled' => true,
    'cache_ttl' => 1800, // 30 minutes
    'max_depth' => 5,
    'auto_permissions' => true, // Auto create permissions for menu items
    'icons' => [
        'supported_libraries' => ['fontawesome', 'feather', 'heroicons', 'material'],
        'default_library' => 'fontawesome',
    ],
],
```
