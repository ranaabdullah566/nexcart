<?php

namespace App\Console\Commands;

use App\Support\Trash;
use Illuminate\Console\Command;

class TrashPurge extends Command
{
    protected $signature = 'nexcart:trash:purge {--days= : Erase records deleted more than this many days ago (default: the Recycle Bin setting)} {--scheduled : Do nothing unless automatic emptying is switched on}';

    protected $description = 'Permanently erase old items from the Recycle Bin';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: setting('trash.keep_days', 0));
        if ($days < 1) {
            $this->option('scheduled') || $this->warn('Set --days or the "empty automatically" setting first.');

            return $this->option('scheduled') ? self::SUCCESS : self::FAILURE;
        }

        $this->info(Trash::purgeOlderThan($days)." item(s) erased (older than {$days} days).");

        return self::SUCCESS;
    }
}
