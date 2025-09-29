<?php

namespace Anwar\DynamicRoles\Commands;

use Anwar\DynamicRoles\Services\MenuService;
use Illuminate\Console\Command;

class ClearMenuCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dynamic-roles:clear-menu-cache
                            {--force : Force clear all cache patterns}
                            {--debug : Show cache keys before and after clearing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all menu-related caches from the dynamic roles system';

    /**
     * Execute the console command.
     */
    public function handle(MenuService $menuService): int
    {
        $this->info('Clearing menu caches...');

        try {
            // Show debug info before clearing if requested
            if ($this->option('debug')) {
                $this->showDebugInfo($menuService, 'before');
            }

            if ($this->option('force')) {
                $this->warn('Force clearing all menu cache patterns...');
                $result = $menuService->forceClearAllMenuCaches();
            } else {
                $result = $this->clearMenuCaches($menuService);
            }

            // Show debug info after clearing if requested
            if ($this->option('debug')) {
                $this->showDebugInfo($menuService, 'after');
            }

            if ($result) {
                $this->info('✅ Menu caches cleared successfully!');

                // Show cache statistics
                $this->displayCacheInfo();

                return Command::SUCCESS;
            } else {
                $this->error('❌ Failed to clear menu caches completely');
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('❌ Error occurred while clearing menu caches: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Clear menu caches with detailed output
     */
    protected function clearMenuCaches(MenuService $menuService): bool
    {
        $this->line('• Clearing tag-based caches...');

        // Use the force clear method which is more comprehensive
        $result = $menuService->forceClearAllMenuCaches();

        if ($result) {
            $this->line('• Clearing specific cache keys...');
            \Illuminate\Support\Facades\Cache::forget('full_menu_tree');

            $this->line('• Cache clearing completed');
        }

        return $result;
    }

    /**
     * Show debug information about cache state
     */
    protected function showDebugInfo(MenuService $menuService, string $timing): void
    {
        $this->newLine();
        $this->line("<comment>Cache Debug Info ({$timing} clearing):</comment>");

        $debugInfo = $menuService->debugMenuCacheKeys();

        if ($debugInfo['error']) {
            $this->error('Debug Error: ' . $debugInfo['error']);
            return;
        }

        if (empty($debugInfo['found_keys'])) {
            $this->line('• No menu-related cache keys found');
        } else {
            foreach ($debugInfo['found_keys'] as $pattern => $keys) {
                $this->line("• Pattern '{$pattern}': " . count($keys) . ' keys');
                if (count($keys) <= 5) {
                    foreach ($keys as $key) {
                        $this->line("  - {$key}");
                    }
                } else {
                    $this->line("  - (showing first 5 of " . count($keys) . ")");
                    for ($i = 0; $i < 5; $i++) {
                        $this->line("  - {$keys[$i]}");
                    }
                }
            }
        }
    }

    /**
     * Display cache configuration information
     */
    protected function displayCacheInfo(): void
    {
        $this->newLine();
        $this->line('<comment>Cache Configuration:</comment>');
        $this->line('• Cache Driver: ' . config('cache.default'));
        $this->line('• Cache Prefix: ' . config('cache.prefix', 'N/A'));
        $this->line('• Dynamic Roles Cache Prefix: ' . config('dynamic-roles.cache.prefix', 'dynamic_roles'));
        $this->line('• Cache TTL: ' . config('dynamic-roles.menu.cache_ttl', 1800) . ' seconds');
        $this->line('• Cache Enabled: ' . (config('dynamic-roles.cache.enabled', true) ? 'Yes' : 'No'));
        $this->line('• Tags Supported: ' . (in_array(config('cache.default'), ['redis', 'memcached']) ? 'Yes' : 'No'));
    }
}
