<?php

namespace Anwar\DynamicRoles\Commands;

use Illuminate\Console\Command;
use Anwar\DynamicRoles\Services\PermissionCacheService;

class ClearCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'dynamic-roles:clear-cache 
                            {--user= : Clear cache for specific user ID}
                            {--role= : Clear cache for specific role ID}
                            {--url= : Clear cache for specific URL}
                            {--method=GET : HTTP method for URL cache clearing}';

    /**
     * The console command description.
     */
    protected $description = 'Clear dynamic roles permission cache';

    protected PermissionCacheService $cacheService;

    public function __construct(PermissionCacheService $cacheService)
    {
        parent::__construct();
        $this->cacheService = $cacheService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $cleared = false;

            if ($userId = $this->option('user')) {
                $this->info("Clearing cache for user ID: {$userId}");
                $this->cacheService->clearUserCache($userId);
                $cleared = true;
            }

            if ($roleId = $this->option('role')) {
                $this->info("Clearing cache for role ID: {$roleId}");
                $this->cacheService->clearRoleCache($roleId);
                $cleared = true;
            }

            if ($url = $this->option('url')) {
                $method = $this->option('method');
                $this->info("Clearing cache for URL: {$method} {$url}");
                $this->cacheService->clearUrlCache($url, $method);
                $cleared = true;
            }

            if (!$cleared) {
                $this->info('Clearing all permission cache...');
                $this->cacheService->clearAll();
            }

            $this->info('Cache cleared successfully!');
            return 0;

        } catch (\Exception $e) {
            $this->error('Failed to clear cache: ' . $e->getMessage());
            return 1;
        }
    }
}
