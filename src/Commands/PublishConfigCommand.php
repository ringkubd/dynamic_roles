<?php

namespace Anwar\DynamicRoles\Commands;

use Illuminate\Console\Command;

class PublishConfigCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'dynamic-roles:publish-config';

    /**
     * The console command description.
     */
    protected $description = 'Publish dynamic roles configuration file';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->call('vendor:publish', [
            '--tag' => 'dynamic-roles-config',
            '--force' => true,
        ]);

        $this->info('Dynamic roles configuration published successfully!');
        
        return 0;
    }
}
