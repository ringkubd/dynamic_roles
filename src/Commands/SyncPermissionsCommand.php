<?php

namespace Anwar\DynamicRoles\Commands;

use Illuminate\Console\Command;
use Anwar\DynamicRoles\Services\UrlPermissionService;

class SyncPermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'dynamic-roles:sync-permissions 
                            {--auto-discover : Auto-discover routes and register them}
                            {--clear-cache : Clear permission cache after sync}';

    /**
     * The console command description.
     */
    protected $description = 'Sync permissions with routes and URLs';

    protected UrlPermissionService $urlPermissionService;

    public function __construct(UrlPermissionService $urlPermissionService)
    {
        parent::__construct();
        $this->urlPermissionService = $urlPermissionService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting permission synchronization...');

        try {
            if ($this->option('auto-discover')) {
                $this->info('Auto-discovering routes...');
                $discovered = $this->urlPermissionService->autoDiscoverRoutes();
                $this->info("Discovered and registered " . count($discovered) . " routes.");
            }

            if ($this->option('clear-cache')) {
                $this->info('Clearing permission cache...');
                app('dynamic-roles.cache')->clearAll();
                $this->info('Cache cleared successfully.');
            }

            $this->info('Permission synchronization completed successfully!');
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to sync permissions: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
