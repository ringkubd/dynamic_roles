<?php

/**
 * Example Integration Guide for Dynamic Roles Package
 * 
 * This file shows how to integrate the Dynamic Roles package
 * into your Laravel application with practical examples.
 */

namespace App\Examples;

use Gunma\DynamicRoles\Facades\DynamicRoles;
use Gunma\DynamicRoles\Services\RolePermissionService;
use Gunma\DynamicRoles\Services\UrlPermissionService;
use Illuminate\Support\Facades\Route;

class DynamicRolesIntegration
{
    /**
     * Example 1: Basic Setup in AppServiceProvider
     */
    public function bootInAppServiceProvider()
    {
        // In your AppServiceProvider::boot() method
        
        // Register common API routes with permissions
        $this->registerApiPermissions();
        
        // Setup role hierarchy
        $this->setupRoleHierarchy();
        
        // Auto-discover routes in development
        if (app()->environment('local')) {
            DynamicRoles::autoDiscoverRoutes();
        }
    }

    /**
     * Example 2: Register API Permissions
     */
    protected function registerApiPermissions()
    {
        $apiRoutes = [
            // User management
            ['/api/users', 'GET', ['users.view'], 'List users'],
            ['/api/users', 'POST', ['users.create'], 'Create user'],
            ['/api/users/{id}', 'GET', ['users.view'], 'View user'],
            ['/api/users/{id}', 'PUT', ['users.edit'], 'Update user'],
            ['/api/users/{id}', 'DELETE', ['users.delete'], 'Delete user'],
            
            // Product management
            ['/api/products', 'GET', ['products.view'], 'List products'],
            ['/api/products', 'POST', ['products.create'], 'Create product'],
            ['/api/products/{id}', 'PUT', ['products.edit'], 'Update product'],
            ['/api/products/{id}', 'DELETE', ['products.delete'], 'Delete product'],
            
            // Order management
            ['/api/orders', 'GET', ['orders.view'], 'List orders'],
            ['/api/orders', 'POST', ['orders.create'], 'Create order'],
            ['/api/orders/{id}', 'PUT', ['orders.edit'], 'Update order'],
            ['/api/orders/{id}/cancel', 'POST', ['orders.cancel'], 'Cancel order'],
        ];

        foreach ($apiRoutes as [$url, $method, $permissions, $description]) {
            DynamicRoles::registerUrl($url, $method, $permissions, [
                'description' => $description,
                'category' => 'api',
                'auto_discovered' => false,
            ]);
        }
    }

    /**
     * Example 3: Setup Role Hierarchy
     */
    protected function setupRoleHierarchy()
    {
        $rolePermissionService = app(RolePermissionService::class);

        $roleHierarchy = [
            'super-admin' => [
                'users.view', 'users.create', 'users.edit', 'users.delete',
                'products.view', 'products.create', 'products.edit', 'products.delete',
                'orders.view', 'orders.create', 'orders.edit', 'orders.cancel',
                'admin.access', 'reports.view',
            ],
            'admin' => [
                'users.view', 'users.create', 'users.edit',
                'products.view', 'products.create', 'products.edit',
                'orders.view', 'orders.edit',
                'admin.access',
            ],
            'manager' => [
                'products.view', 'products.edit',
                'orders.view', 'orders.edit',
                'users.view',
            ],
            'employee' => [
                'products.view',
                'orders.view',
            ],
            'customer' => [
                'products.view',
                'orders.view', 'orders.create',
            ],
        ];

        foreach ($roleHierarchy as $roleName => $permissions) {
            // Create role if it doesn't exist
            try {
                $rolePermissionService->createRole($roleName, $permissions);
            } catch (\Exception $e) {
                // Role might already exist, just assign permissions
                $role = \Spatie\Permission\Models\Role::where('name', $roleName)->first();
                if ($role) {
                    $rolePermissionService->assignPermissionsToRole($role, $permissions);
                }
            }
        }
    }

    /**
     * Example 4: Custom Middleware Usage in Routes
     */
    public function registerProtectedRoutes()
    {
        // In your routes/api.php file

        // Basic protection - uses URL-based permissions
        Route::middleware(['auth:sanctum', 'dynamic.permission'])->group(function () {
            Route::apiResource('users', \App\Http\Controllers\UserController::class);
            Route::apiResource('products', \App\Http\Controllers\ProductController::class);
        });

        // Specific permission requirement
        Route::middleware(['auth:sanctum', 'dynamic.permission:admin.access'])
            ->prefix('admin')
            ->group(function () {
                Route::get('/dashboard', [\App\Http\Controllers\AdminController::class, 'dashboard']);
                Route::get('/reports', [\App\Http\Controllers\AdminController::class, 'reports']);
            });

        // Role-based protection
        Route::middleware(['auth:sanctum', 'dynamic.role:admin,manager'])
            ->group(function () {
                Route::get('/management/stats', [\App\Http\Controllers\ManagementController::class, 'stats']);
            });
    }

    /**
     * Example 5: Controller Integration
     */
    public function controllerExample()
    {
        // In your controllers, you can check permissions programmatically
        
        class ProductController extends \Illuminate\Routing\Controller
        {
            protected $urlPermissionService;

            public function __construct(UrlPermissionService $urlPermissionService)
            {
                $this->urlPermissionService = $urlPermissionService;
            }

            public function index(\Illuminate\Http\Request $request)
            {
                // Manual permission check
                if (!$this->urlPermissionService->checkUrlPermission($request->user(), '/api/products', 'GET')) {
                    abort(403, 'Insufficient permissions');
                }

                // Your logic here
                return response()->json(['products' => []]);
            }

            public function store(\Illuminate\Http\Request $request)
            {
                // The middleware already handles this, but you can add additional checks
                $user = $request->user();
                
                // Check if user can create premium products
                if ($request->input('is_premium') && !$user->hasPermissionTo('products.create.premium')) {
                    return response()->json(['error' => 'Cannot create premium products'], 403);
                }

                // Your logic here
            }
        }
    }

    /**
     * Example 6: Frontend API Integration (for Next.js)
     */
    public function frontendIntegrationExample()
    {
        /*
        // TypeScript interfaces for frontend
        
        interface User {
            id: number;
            name: string;
            email: string;
            roles: string[];
            permissions: string[];
        }

        interface PermissionCheck {
            url: string;
            method: string;
            has_permission: boolean;
        }

        // Permission service for Next.js
        class PermissionService {
            private apiBase = '/api/dynamic-roles';

            async checkPermission(url: string, method: string): Promise<boolean> {
                try {
                    const response = await fetch(`${this.apiBase}/urls/check-permission`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${this.getToken()}`,
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

            async getUserPermissions(userId: number): Promise<string[]> {
                const response = await fetch(`${this.apiBase}/users/${userId}/permissions`, {
                    headers: {
                        'Authorization': `Bearer ${this.getToken()}`,
                    },
                });

                const result = await response.json();
                return result.data.permissions;
            }

            private getToken(): string {
                // Your token retrieval logic
                return localStorage.getItem('auth_token') || '';
            }
        }

        // React component example
        function ProductManagement() {
            const [canEdit, setCanEdit] = useState(false);
            const [canDelete, setCanDelete] = useState(false);
            const permissionService = new PermissionService();

            useEffect(() => {
                const checkPermissions = async () => {
                    const [editPermission, deletePermission] = await Promise.all([
                        permissionService.checkPermission('/api/products', 'PUT'),
                        permissionService.checkPermission('/api/products', 'DELETE'),
                    ]);

                    setCanEdit(editPermission);
                    setCanDelete(deletePermission);
                };

                checkPermissions();
            }, []);

            return (
                <div>
                    <h2>Products</h2>
                    {canEdit && <button>Edit Product</button>}
                    {canDelete && <button>Delete Product</button>}
                </div>
            );
        }
        */
    }

    /**
     * Example 7: Database Seeders for Initial Setup
     */
    public function seederExample()
    {
        /*
        // Create a seeder: php artisan make:seeder DynamicRolesSeeder
        
        class DynamicRolesSeeder extends \Illuminate\Database\Seeder
        {
            public function run()
            {
                $rolePermissionService = app(RolePermissionService::class);

                // Create basic permissions
                $permissions = [
                    'users.view', 'users.create', 'users.edit', 'users.delete',
                    'products.view', 'products.create', 'products.edit', 'products.delete',
                    'orders.view', 'orders.create', 'orders.edit', 'orders.cancel',
                    'admin.access', 'reports.view',
                ];

                foreach ($permissions as $permission) {
                    $rolePermissionService->createPermission($permission);
                }

                // Create roles with permissions
                $rolePermissionService->createRole('super-admin', $permissions);
                $rolePermissionService->createRole('admin', array_slice($permissions, 0, -2));
                $rolePermissionService->createRole('user', ['products.view', 'orders.view', 'orders.create']);

                // Create test users
                $superAdmin = \App\Models\User::create([
                    'name' => 'Super Admin',
                    'email' => 'superadmin@example.com',
                    'password' => bcrypt('password'),
                ]);
                $superAdmin->assignRole('super-admin');

                $admin = \App\Models\User::create([
                    'name' => 'Admin User',
                    'email' => 'admin@example.com',
                    'password' => bcrypt('password'),
                ]);
                $admin->assignRole('admin');

                // Register URLs
                DynamicRoles::autoDiscoverRoutes();
            }
        }
        */
    }

    /**
     * Example 8: Event Listeners for Permission Changes
     */
    public function eventListenerExample()
    {
        /*
        // Create event listener for role assignments
        
        class RoleAssignedListener
        {
            public function handle(\Spatie\Permission\Events\RoleAssigned $event)
            {
                // Clear user cache when role is assigned
                app(\Gunma\DynamicRoles\Services\PermissionCacheService::class)
                    ->clearUserCache($event->model->id);

                // Log the change
                \Log::info('Role assigned', [
                    'user_id' => $event->model->id,
                    'role' => $event->role->name,
                ]);

                // Send notification to user
                $event->model->notify(new RoleAssignedNotification($event->role));
            }
        }

        // Register in EventServiceProvider
        protected $listen = [
            \Spatie\Permission\Events\RoleAssigned::class => [
                RoleAssignedListener::class,
            ],
            \Spatie\Permission\Events\PermissionAssigned::class => [
                PermissionAssignedListener::class,
            ],
        ];
        */
    }

    /**
     * Example 9: Custom Artisan Commands
     */
    public function customCommandExample()
    {
        /*
        // Create custom command: php artisan make:command SetupDynamicRoles
        
        class SetupDynamicRoles extends \Illuminate\Console\Command
        {
            protected $signature = 'app:setup-dynamic-roles 
                                    {--environment=production : Environment to setup for}';
            
            protected $description = 'Setup dynamic roles for the application';

            public function handle()
            {
                $env = $this->option('environment');
                
                $this->info("Setting up dynamic roles for {$env} environment...");

                // Setup based on environment
                if ($env === 'production') {
                    $this->setupProductionRoles();
                } else {
                    $this->setupDevelopmentRoles();
                }

                // Auto-discover routes
                $this->call('dynamic-roles:sync-permissions', ['--auto-discover' => true]);

                $this->info('Dynamic roles setup completed!');
            }

            private function setupProductionRoles()
            {
                // Production-specific role setup
                $this->info('Setting up production roles...');
                // Your logic here
            }

            private function setupDevelopmentRoles()
            {
                // Development-specific role setup with test data
                $this->info('Setting up development roles...');
                // Your logic here
            }
        }
        */
    }

    /**
     * Example 10: Testing Integration
     */
    public function testingExample()
    {
        /*
        // Feature test example
        
        class DynamicRolesTest extends \Tests\TestCase
        {
            use \Illuminate\Foundation\Testing\RefreshDatabase;

            public function test_user_with_permission_can_access_endpoint()
            {
                $user = \App\Models\User::factory()->create();
                $user->givePermissionTo('products.view');

                DynamicRoles::registerUrl('/api/products', 'GET', ['products.view']);

                $response = $this->actingAs($user)
                    ->getJson('/api/products');

                $response->assertStatus(200);
            }

            public function test_user_without_permission_cannot_access_endpoint()
            {
                $user = \App\Models\User::factory()->create();

                DynamicRoles::registerUrl('/api/admin', 'GET', ['admin.access']);

                $response = $this->actingAs($user)
                    ->getJson('/api/admin');

                $response->assertStatus(403);
            }

            public function test_api_endpoints_work_correctly()
            {
                $admin = \App\Models\User::factory()->create();
                $admin->assignRole('super-admin');

                // Test create URL endpoint
                $response = $this->actingAs($admin)
                    ->postJson('/api/dynamic-roles/urls', [
                        'url' => '/api/test',
                        'method' => 'GET',
                        'permissions' => ['test.view'],
                    ]);

                $response->assertStatus(201);

                // Test permission check endpoint
                $response = $this->actingAs($admin)
                    ->postJson('/api/dynamic-roles/urls/check-permission', [
                        'url' => '/api/test',
                        'method' => 'GET',
                    ]);

                $response->assertStatus(200)
                    ->assertJsonPath('data.has_permission', true);
            }
        }
        */
    }
}
