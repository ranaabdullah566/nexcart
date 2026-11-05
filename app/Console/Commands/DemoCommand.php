<?php

namespace App\Console\Commands;

use App\Services\DemoData;
use App\Support\BusinessProfile;
use Illuminate\Console\Command;

class DemoCommand extends Command
{
    protected $signature = 'nexcart:demo {--type= : general, fastfood, pharmacy or medical_store (default: the store\'s business type)} {--remove : Remove the sample data instead}';

    protected $description = 'Load (or remove) sample products for the store\'s business type';

    public function handle(DemoData $demo): int
    {
        if ($this->option('remove')) {
            $this->info($demo->remove().' sample product(s) removed.');

            return self::SUCCESS;
        }

        $type = $this->option('type') ?: (string) setting('business.type', 'general');
        if (! array_key_exists($type, BusinessProfile::all())) {
            $this->error('Unknown business type. Use: '.implode(', ', array_keys(BusinessProfile::all())));

            return self::FAILURE;
        }

        try {
            $this->info($demo->load($type)." sample product(s) added for {$type}.");
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
