<?php

namespace Anwar\DynamicRoles;

use Illuminate\Support\ServiceProvider;
use Anwar\DynamicRoles\Commands\{
    SyncPermissionsCommand,
    ClearCacheCommand,
    ClearMenuCacheCommand,
    PublishConfigCommand
};
use Anwar\DynamicRoles\Middleware\{
    DynamicPermissionMiddleware,
    DynamicRoleMiddleware
};
use Anwar\DynamicRoles\Services\{
    PermissionCacheService,
    UrlPermissionService,
    RolePermissionService,
    MenuService
};

class DynamicRolesServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishConfig();
        $this->publishMigrations();
        $this->loadRoutes();
        $this->loadViews();
        $this->registerMiddleware();
        $this->registerCommands();
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/dynamic-roles.php',
            'dynamic-roles'
        );

        $this->app->singleton(PermissionCacheService::class);
        $this->app->singleton(UrlPermissionService::class);
        $this->app->singleton(RolePermissionService::class);
        $this->app->singleton(MenuService::class);

        $this->app->alias(PermissionCacheService::class, 'dynamic-roles.cache');
        $this->app->alias(UrlPermissionService::class, 'dynamic-roles.url');
        $this->app->alias(RolePermissionService::class, 'dynamic-roles.roles');
        $this->app->alias(MenuService::class, 'dynamic-roles.menu');
    }

    /**
     * Publish configuration file.
     */
    protected function publishConfig(): void
    {
        $this->publishes([
            __DIR__ . '/../config/dynamic-roles.php' => config_path('dynamic-roles.php'),
        ], 'dynamic-roles-config');
    }

    /**
     * Publish migration files.
     */
    protected function publishMigrations(): void
    {
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'dynamic-roles-migrations');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Load package routes.
     */
    protected function loadRoutes(): void
    {
        if (config('dynamic-roles.enable_api_routes', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        }

        if (config('dynamic-roles.enable_web_routes', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }
    }

    /**
     * Load package views.
     */
    protected function loadViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'dynamic-roles');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/dynamic-roles'),
        ], 'dynamic-roles-views');
    }

    /**
     * Register middleware.
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];

        $router->aliasMiddleware('dynamic.permission', DynamicPermissionMiddleware::class);
        $router->aliasMiddleware('dynamic.role', DynamicRoleMiddleware::class);
    }

    /**
     * Register artisan commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncPermissionsCommand::class,
                ClearCacheCommand::class,
                ClearMenuCacheCommand::class,
                PublishConfigCommand::class,
            ]);
        }
    }
}
