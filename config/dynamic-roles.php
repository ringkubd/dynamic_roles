<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Table Names Configuration
    |--------------------------------------------------------------------------
    |
    | Configure table names used by the package
    |
    */
    'table_names' => [
        'dynamic_urls' => env('DYNAMIC_ROLES_URLS_TABLE', 'dynamic_urls'),
        'dynamic_url_permissions' => env('DYNAMIC_ROLES_URL_PERMISSIONS_TABLE', 'dynamic_url_permissions'),
        'dynamic_role_urls' => env('DYNAMIC_ROLES_ROLE_URLS_TABLE', 'dynamic_role_urls'),
        'dynamic_permission_checks' => env('DYNAMIC_ROLES_PERMISSION_CHECKS_TABLE', 'dynamic_permission_checks'),
        'dynamic_menus' => env('DYNAMIC_ROLES_MENUS_TABLE', 'dynamic_menus'),
        'dynamic_menu_permissions' => env('DYNAMIC_ROLES_MENU_PERMISSIONS_TABLE', 'dynamic_menu_permissions'),
        'dynamic_menu_roles' => env('DYNAMIC_ROLES_MENU_ROLES_TABLE', 'dynamic_menu_roles'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Menu Configuration
    |--------------------------------------------------------------------------
    |
    | Configure menu behavior and features
    |
    */
    'menu' => [
        'enabled' => env('DYNAMIC_ROLES_MENU_ENABLED', true),
        'cache_enabled' => env('DYNAMIC_ROLES_MENU_CACHE_ENABLED', true),
        'cache_ttl' => env('DYNAMIC_ROLES_MENU_CACHE_TTL', 1800), // 30 minutes
        'max_depth' => env('DYNAMIC_ROLES_MENU_MAX_DEPTH', 5),
        'auto_permissions' => env('DYNAMIC_ROLES_MENU_AUTO_PERMISSIONS', true), // Auto create permissions for menu items
        'icons' => [
            'supported_libraries' => ['fontawesome', 'feather', 'heroicons', 'material'],
            'default_library' => env('DYNAMIC_ROLES_MENU_DEFAULT_ICON_LIBRARY', 'fontawesome'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching mechanism for role and permission data
    |
    */
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

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Configure database tables and relationships
    |
    */
    'database' => [
        'tables' => [
            'dynamic_urls' => 'dynamic_urls',
            'dynamic_url_permissions' => 'dynamic_url_permissions',
            'dynamic_role_urls' => 'dynamic_role_urls',
        ],
        'connection' => env('DYNAMIC_ROLES_DB_CONNECTION', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Configure API routes and behavior
    |
    */
    'api' => [
        'enable_api_routes' => env('DYNAMIC_ROLES_ENABLE_API', true),
        'route_prefix' => env('DYNAMIC_ROLES_API_PREFIX', 'api/dynamic-roles'),
        'middleware' => [
            'api',
            'auth:sanctum', // Change this based on your auth setup
        ],
        'rate_limit' => env('DYNAMIC_ROLES_RATE_LIMIT', '60,1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware Configuration
    |--------------------------------------------------------------------------
    |
    | Configure middleware behavior
    |
    */
    'middleware' => [
        'auto_register_urls' => env('DYNAMIC_ROLES_AUTO_REGISTER_URLS', true),
        'excluded_patterns' => [
            '/api/dynamic-roles/*',
            '/health*',
            '/status*',
            '/_debugbar/*',
            '/telescope/*',
        ],
        'excluded_methods' => ['OPTIONS', 'HEAD'],
        'default_permissions' => ['view'], // Default permissions for new URLs
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Configure security features
    |
    */
    'security' => [
        'super_admin_role' => env('DYNAMIC_ROLES_SUPER_ADMIN', 'super-admin'),
        'bypass_permissions' => env('DYNAMIC_ROLES_BYPASS_PERMISSIONS', false),
        'log_permission_checks' => env('DYNAMIC_ROLES_LOG_CHECKS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Configuration
    |--------------------------------------------------------------------------
    |
    | Configure UI-related features
    |
    */
    'ui' => [
        'enable_web_routes' => env('DYNAMIC_ROLES_ENABLE_WEB_UI', false),
        'route_prefix' => env('DYNAMIC_ROLES_WEB_PREFIX', 'admin/dynamic-roles'),
        'pagination_per_page' => env('DYNAMIC_ROLES_PAGINATION', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-Discovery Configuration
    |--------------------------------------------------------------------------
    |
    | Configure automatic URL and permission discovery
    |
    */
    'discovery' => [
        'enabled' => env('DYNAMIC_ROLES_AUTO_DISCOVERY', true),
        'scan_routes' => env('DYNAMIC_ROLES_SCAN_ROUTES', true),
        'scan_controllers' => env('DYNAMIC_ROLES_SCAN_CONTROLLERS', true),
        'permission_patterns' => [
            'create' => ['store', 'create'],
            'read' => ['index', 'show', 'view'],
            'update' => ['update', 'edit'],
            'delete' => ['destroy', 'delete'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Configure performance optimizations
    |
    */
    'performance' => [
        'eager_load_relationships' => true,
        'cache_user_permissions' => true,
        'batch_permission_checks' => true,
        'preload_permissions' => env('DYNAMIC_ROLES_PRELOAD_PERMISSIONS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Configuration
    |--------------------------------------------------------------------------
    |
    | Configure notifications for permission changes
    |
    */
    'notifications' => [
        'enabled' => env('DYNAMIC_ROLES_NOTIFICATIONS', false),
        'channels' => ['mail', 'database'],
        'notify_on' => [
            'role_assigned',
            'permission_granted',
            'permission_revoked',
        ],
    ],
];
