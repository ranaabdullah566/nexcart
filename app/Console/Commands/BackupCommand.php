<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupCommand extends Command
{
    protected $signature = 'nexcart:backup {--keep= : How many backups to keep (default: the Backups setting)} {--no-uploads : Database only} {--scheduled : Run only if automatic backups are switched on}';

    protected $description = 'Back up the database and uploaded files into storage/app/backups';

    public function handle(BackupService $backups): int
    {
        if ($this->option('scheduled') && ! setting('backup.auto', false)) {
            return self::SUCCESS;
        }

        try {
            $path = $backups->create(! $this->option('no-uploads'));
        } catch (\Throwable $e) {
            $this->error('Backup failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Backup written: '.$path.' ('.number_format(filesize($path) / 1024, 1).' KB)');
        $removed = $backups->prune((int) ($this->option('keep') ?: setting('backup.keep', 7)));
        $removed && $this->line("Removed {$removed} old backup(s).");

        return self::SUCCESS;
    }
}
