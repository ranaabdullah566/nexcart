<?php

namespace App\Console\Commands;

use App\Services\DemoData;
use Illuminate\Console\Command;

class DemoCommand extends Command
{
    protected $signature = 'nexcart:demo {--remove : Remove the sample data instead}';

    protected $description = 'Load (or remove) sample products for the pharmacy';

    public function handle(DemoData $demo): int
    {
        if ($this->option('remove')) {
            $this->info($demo->remove().' sample product(s) removed.');

            return self::SUCCESS;
        }

        try {
            $this->info($demo->load('pharmacy').' sample medicine(s) added.');
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
