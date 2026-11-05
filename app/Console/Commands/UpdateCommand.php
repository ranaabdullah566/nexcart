<?php

namespace App\Console\Commands;

use App\Support\Installer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class UpdateCommand extends Command
{
    protected $signature = 'nexcart:update {--no-backup : Skip the safety backup}';

    protected $description = 'After replacing the application files: back up, migrate, refresh permissions and caches';

    public function handle(): int
    {
        $this->info('NexCart '.config('nexcart.version'));

        if (! $this->option('no-backup')) {
            $this->callSilent('nexcart:backup', ['--keep' => max(3, (int) setting('backup.keep', 7))]) === 0
                ? $this->line('• Safety backup created')
                : $this->warn('• Backup failed — continuing without it (use --no-backup to silence this)');
        }

        Artisan::call('down', ['--retry' => 60]);
        try {
            $this->call('migrate', ['--force' => true]);
            $this->call('nexcart:sync-permissions');
            if (setting()->isInstalled() && ! Installer::locked()) {
                Installer::lock();   // sites installed before the web installer existed
                $this->line('• Installer locked');
            }
            foreach (['optimize:clear', 'view:clear'] as $c) {
                Artisan::call($c);
            }
            if (app()->environment('production')) {
                foreach (['config:cache', 'route:cache', 'view:cache'] as $c) {
                    Artisan::call($c);
                }
                $this->line('• Caches rebuilt');
            }
        } finally {
            Artisan::call('up');
        }

        $this->info('Update finished.');

        return self::SUCCESS;
    }
}
