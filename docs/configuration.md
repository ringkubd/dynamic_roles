# Configuration

This document covers all configuration options available in the Dynamic Roles Package.

## Configuration File

The main configuration file is located at `config/dynamic-roles.php`. You can publish it using:

```bash
php artisan vendor:publish --tag=dynamic-roles-config
```

## Configuration Options

### Table Names

Configure custom table names for the package:

```php
'table_names' => [
    'dynamic_urls' => env('DYNAMIC_ROLES_URLS_TABLE', 'dynamic_urls'),
    'dynamic_url_permissions' => env('DYNAMIC_ROLES_URL_PERMISSIONS_TABLE', 'dynamic_url_permissions'),
    'dynamic_role_urls' => env('DYNAMIC_ROLES_ROLE_URLS_TABLE', 'dynamic_role_urls'),
    'dynamic_permission_checks' => env('DYNAMIC_ROLES_PERMISSION_CHECKS_TABLE', 'dynamic_permission_checks'),
    'dynamic_menus' => env('DYNAMIC_ROLES_MENUS_TABLE', 'dynamic_menus'),
    'dynamic_menu_permissions' => env('DYNAMIC_ROLES_MENU_PERMISSIONS_TABLE', 'dynamic_menu_permissions'),
    'dynamic_menu_roles' => env('DYNAMIC_ROLES_MENU_ROLES_TABLE', 'dynamic_menu_roles'),
],
```

### Menu Configuration

Configure menu system behavior:

```php
'menu' => [
    'enabled' => env('DYNAMIC_ROLES_MENU_ENABLED', true),
    'cache_enabled' => env('DYNAMIC_ROLES_MENU_CACHE_ENABLED', true),
    'cache_ttl' => env('DYNAMIC_ROLES_MENU_CACHE_TTL', 1800), // 30 minutes
    'max_depth' => env('DYNAMIC_ROLES_MENU_MAX_DEPTH', 5),
    'auto_permissions' => env('DYNAMIC_ROLES_MENU_AUTO_PERMISSIONS', true),
    'icons' => [
        'supported_libraries' => ['fontawesome', 'feather', 'heroicons', 'material'],
        'default_library' => env('DYNAMIC_ROLES_MENU_ICON_LIBRARY', 'fontawesome'),
    ],
],
```

### Cache Configuration

Configure caching for optimal performance:

```php
'cache' => [
    'enabled' => env('DYNAMIC_ROLES_CACHE_ENABLED', true),
    'driver' => env('DYNAMIC_ROLES_CACHE_DRIVER', 'redis'),
    'prefix' => env('DYNAMIC_ROLES_CACHE_PREFIX', 'dynamic_roles'),
    'ttl' => env('DYNAMIC_ROLES_CACHE_TTL', 3600), // 1 hour
    'tags' => [
        'permissions' => 'dynamic_roles_permissions',
        'roles' => 'dynamic_roles_roles',
        'urls' => 'dynamic_roles_urls',
        'menus' => 'dynamic_roles_menus',
    ],
],
```

### Auto-Discovery Configuration

Configure automatic route discovery:

```php
'discovery' => [
    'enabled' => env('DYNAMIC_ROLES_AUTO_DISCOVERY', true),
    'auto_register_urls' => env('DYNAMIC_ROLES_AUTO_REGISTER_URLS', true),
    'scan_routes' => env('DYNAMIC_ROLES_SCAN_ROUTES', true),
    'scan_controllers' => env('DYNAMIC_ROLES_SCAN_CONTROLLERS', true),
    'permission_patterns' => [
        'create' => ['store', 'create'],
        'read' => ['index', 'show', 'view'],
        'update' => ['update', 'edit'],
        'delete' => ['destroy', 'delete'],
    ],
],
```

### API Configuration

Configure API behavior and routes:

```php
'api' => [
    'enable_api_routes' => env('DYNAMIC_ROLES_ENABLE_API', true),
    'route_prefix' => env('DYNAMIC_ROLES_API_PREFIX', 'api/dynamic-roles'),
    'middleware' => ['api', 'auth:sanctum'],
    'rate_limiting' => [
        'enabled' => env('DYNAMIC_ROLES_API_RATE_LIMITING', true),
        'per_minute' => env('DYNAMIC_ROLES_API_RATE_LIMIT', 60),
    ],
],
```

### Database Configuration

Configure database connections and behavior:

```php
'database' => [
    'tables' => [
        'dynamic_urls' => 'dynamic_urls',
        'dynamic_url_permissions' => 'dynamic_url_permissions',
        'dynamic_role_urls' => 'dynamic_role_urls',
    ],
    'connection' => env('DYNAMIC_ROLES_DB_CONNECTION', null),
],
```

### Performance Configuration

Optimize package performance:

```php
'performance' => [
    'eager_load_relationships' => true,
    'cache_user_permissions' => true,
    'batch_permission_checks' => true,
    'preload_permissions' => env('DYNAMIC_ROLES_PRELOAD_PERMISSIONS', true),
],
```

### Security Configuration

Configure security settings:

```php
'security' => [
    'super_admin_role' => env('DYNAMIC_ROLES_SUPER_ADMIN', 'super-admin'),
    'bypass_permissions' => env('DYNAMIC_ROLES_BYPASS_PERMISSIONS', false),
    'log_permission_checks' => env('DYNAMIC_ROLES_LOG_CHECKS', false),
    'audit_enabled' => env('DYNAMIC_ROLES_AUDIT_ENABLED', true),
],
```

### Notification Configuration

Configure notifications for permission changes:

```php
'notifications' => [
    'enabled' => env('DYNAMIC_ROLES_NOTIFICATIONS', false),
    'channels' => ['mail', 'database'],
    'notify_on' => [
        'role_assigned',
        'permission_granted',
        'permission_revoked',
    ],
],
```

## Environment Variables

Add these variables to your `.env` file for easy configuration:

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

# Menu System
DYNAMIC_ROLES_MENU_ENABLED=true
DYNAMIC_ROLES_MENU_CACHE_ENABLED=true
DYNAMIC_ROLES_MENU_CACHE_TTL=1800
DYNAMIC_ROLES_MENU_MAX_DEPTH=5

# Performance
DYNAMIC_ROLES_PRELOAD_PERMISSIONS=true

# Notifications
DYNAMIC_ROLES_NOTIFICATIONS=false

# Database
DYNAMIC_ROLES_DB_CONNECTION=mysql
```

## Advanced Configuration

### Custom Permission Patterns

You can define custom patterns for auto-discovery:

```php
'discovery' => [
    'permission_patterns' => [
        'create' => ['store', 'create', 'add', 'make'],
        'read' => ['index', 'show', 'view', 'list', 'get'],
        'update' => ['update', 'edit', 'modify', 'patch'],
        'delete' => ['destroy', 'delete', 'remove', 'trash'],
        'admin' => ['admin', 'manage', 'control'],
        'export' => ['export', 'download', 'backup'],
        'import' => ['import', 'upload', 'restore'],
    ],
],
```

### Custom Middleware

You can specify custom middleware for API routes:

```php
'api' => [
    'middleware' => [
        'api',
        'auth:sanctum',
        'verified',
        'throttle:api',
        'custom.middleware'
    ],
],
```

### Database Table Prefixes

If you need to use table prefixes:

```php
'table_names' => [
    'dynamic_urls' => 'app_dynamic_urls',
    'dynamic_url_permissions' => 'app_dynamic_url_permissions',
    // ... other tables
],
```

This allows for better organization in shared database environments.