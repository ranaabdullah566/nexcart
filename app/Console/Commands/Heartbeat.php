<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class Heartbeat extends Command
{
    protected $signature = 'nexcart:heartbeat';

    protected $description = 'Record that the scheduler is running (shown on the System page)';

    public function handle(): int
    {
        Cache::forever('nexcart.heartbeat', now()->timestamp);

        return self::SUCCESS;
    }
}
