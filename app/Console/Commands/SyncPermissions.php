<?php

namespace App\Console\Commands;

use Database\Seeders\RolesSeeder;
use Illuminate\Console\Command;

class SyncPermissions extends Command
{
    protected $signature = 'nexcart:sync-permissions';

    protected $description = 'Create any new permissions and reset the built-in roles to their defaults (run after updates)';

    public function handle(): int
    {
        (new RolesSeeder)->run();
        $this->info('Roles and permissions synced.');

        return self::SUCCESS;
    }
}
