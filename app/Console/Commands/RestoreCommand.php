<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RestoreCommand extends Command
{
    protected $signature = 'nexcart:restore {file : Backup file name (in storage/app/backups) or a full path} {--force : Do not ask for confirmation}';

    protected $description = 'Restore a backup: REPLACES the database and uploaded files';

    public function handle(BackupService $backups): int
    {
        $arg = $this->argument('file');
        $path = is_file($arg) ? $arg : null;
        try {
            $path ??= $backups->path(basename($arg));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('This will REPLACE the current database and uploaded files with the backup. Continue?')) {
            return self::FAILURE;
        }

        Artisan::call('down', ['--retry' => 60]);
        try {
            $r = $backups->restore($path);
            $this->info("Restored backup from {$r['created_at']} (NexCart {$r['version']}), {$r['files']} file(s).");
            $this->line('Now run: php artisan nexcart:update   (applies any newer migrations)');
        } catch (\Throwable $e) {
            $this->error('Restore failed: '.$e->getMessage());

            return self::FAILURE;
        } finally {
            Artisan::call('up');
        }

        return self::SUCCESS;
    }
}
