<?php

namespace Anwar\DynamicRoles\Tests;

use PHPUnit\Framework\TestCase;

class PackageStructureTest extends TestCase
{
    public function test_package_structure_exists()
    {
        $packageRoot = __DIR__ . '/..';
        
        // Check main files
        $this->assertFileExists($packageRoot . '/composer.json');
        $this->assertFileExists($packageRoot . '/README.md');
        $this->assertFileExists($packageRoot . '/install.sh');
        
        // Check configuration
        $this->assertFileExists($packageRoot . '/config/dynamic-roles.php');
        
        // Check source files
        $this->assertFileExists($packageRoot . '/src/DynamicRolesServiceProvider.php');
        
        // Check models
        $this->assertFileExists($packageRoot . '/src/Models/DynamicUrl.php');
        $this->assertFileExists($packageRoot . '/src/Models/DynamicPermissionCheck.php');
        $this->assertFileExists($packageRoot . '/src/Models/DynamicMenu.php');
        
        // Check services
        $this->assertFileExists($packageRoot . '/src/Services/PermissionCacheService.php');
        $this->assertFileExists($packageRoot . '/src/Services/UrlPermissionService.php');
        $this->assertFileExists($packageRoot . '/src/Services/RolePermissionService.php');
        $this->assertFileExists($packageRoot . '/src/Services/MenuService.php');
        
        // Check middleware
        $this->assertFileExists($packageRoot . '/src/Middleware/DynamicPermissionMiddleware.php');
        $this->assertFileExists($packageRoot . '/src/Middleware/DynamicRoleMiddleware.php');
        
        // Check commands
        $this->assertFileExists($packageRoot . '/src/Commands/SyncPermissionsCommand.php');
        $this->assertFileExists($packageRoot . '/src/Commands/ClearCacheCommand.php');
        $this->assertFileExists($packageRoot . '/src/Commands/PublishConfigCommand.php');
        
        // Check controllers
        $this->assertFileExists($packageRoot . '/src/Http/Controllers/UrlPermissionController.php');
        $this->assertFileExists($packageRoot . '/src/Http/Controllers/RolePermissionController.php');
        $this->assertFileExists($packageRoot . '/src/Http/Controllers/MenuController.php');
        
        // Check routes
        $this->assertFileExists($packageRoot . '/routes/api.php');
        
        // Check migrations
        $this->assertFileExists($packageRoot . '/database/migrations/2024_01_01_000001_create_dynamic_urls_table.php');
        $this->assertFileExists($packageRoot . '/database/migrations/2024_01_01_000002_create_dynamic_url_permissions_table.php');
        $this->assertFileExists($packageRoot . '/database/migrations/2024_01_01_000003_create_dynamic_role_urls_table.php');
        $this->assertFileExists($packageRoot . '/database/migrations/2024_01_01_000004_create_dynamic_permission_checks_table.php');
        $this->assertFileExists($packageRoot . '/database/migrations/2024_01_01_000005_create_dynamic_menus_table.php');
        $this->assertFileExists($packageRoot . '/database/migrations/2024_01_01_000006_create_dynamic_menu_permissions_table.php');
        $this->assertFileExists($packageRoot . '/database/migrations/2024_01_01_000007_create_dynamic_menu_roles_table.php');
        
        // Check facades
        $this->assertFileExists($packageRoot . '/src/Facades/DynamicRoles.php');
    }

    public function test_composer_json_structure()
    {
        $composerJson = json_decode(file_get_contents(__DIR__ . '/../composer.json'), true);
        
        $this->assertArrayHasKey('name', $composerJson);
        $this->assertEquals('anwar/dynamic-roles', $composerJson['name']);
        
        $this->assertArrayHasKey('autoload', $composerJson);
        $this->assertArrayHasKey('psr-4', $composerJson['autoload']);
        $this->assertArrayHasKey('Anwar\\DynamicRoles\\', $composerJson['autoload']['psr-4']);
        
        $this->assertArrayHasKey('require', $composerJson);
        $this->assertArrayHasKey('spatie/laravel-permission', $composerJson['require']);
    }

    public function test_config_file_structure()
    {
        $config = include __DIR__ . '/../config/dynamic-roles.php';
        
        $this->assertIsArray($config);
        
        // Check main configuration sections
        $this->assertArrayHasKey('cache', $config);
        $this->assertArrayHasKey('database', $config);
        $this->assertArrayHasKey('api', $config);
        $this->assertArrayHasKey('middleware', $config);
        $this->assertArrayHasKey('security', $config);
        $this->assertArrayHasKey('discovery', $config);
        
        // Check cache configuration
        $this->assertArrayHasKey('enabled', $config['cache']);
        $this->assertArrayHasKey('driver', $config['cache']);
        $this->assertArrayHasKey('ttl', $config['cache']);
        
        // Check database configuration
        $this->assertArrayHasKey('tables', $config['database']);
        $this->assertArrayHasKey('dynamic_urls', $config['database']['tables']);
    }
}
