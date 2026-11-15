<?php

namespace App\Console\Commands;

use App\Services\HomeDemo;
use Illuminate\Console\Command;

class HomeDemoCommand extends Command
{
    protected $signature = 'nexcart:home-demo {--remove : Remove the sample home sections instead}';

    protected $description = 'Add (or remove) one switched-on sample section of every kind to the home page';

    public function handle(HomeDemo $demo): int
    {
        if ($this->option('remove')) {
            $this->info($demo->remove().' sample section(s) removed.');

            return self::SUCCESS;
        }
        try {
            $r = $demo->load();
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
        $this->info($r['added'].' sample section(s) added and switched on.');
        foreach ($r['warnings'] as $w) {
            $this->warn($w);
        }

        return self::SUCCESS;
    }
}
