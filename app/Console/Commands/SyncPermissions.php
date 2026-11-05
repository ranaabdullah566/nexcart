<?php

namespace App\Console\Commands;

use Database\Seeders\RolesSeeder;
use Illuminate\Console\Command;

class SyncPermissions extends Command
{
    protected $signature = 'nexcart:sync-permissions {--reset-roles : Put the built-in roles back to their factory defaults (discards your changes to them)}';

    protected $description = 'Create any new permissions (run after updates). Roles you edited keep your choices; the owner always gets everything';

    public function handle(): int
    {
        (new RolesSeeder)->run((bool) $this->option('reset-roles'));
        $this->info($this->option('reset-roles') ? 'Built-in roles reset to their defaults.' : 'Permissions synced. Your role changes were kept.');

        return self::SUCCESS;
    }
}
