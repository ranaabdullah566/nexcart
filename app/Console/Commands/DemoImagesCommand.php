<?php

namespace App\Console\Commands;

use App\Services\DemoImages;
use Illuminate\Console\Command;

class DemoImagesCommand extends Command
{
    protected $signature = 'nexcart:demo-images {--products=60 : How many products without a picture get one} {--remove : Remove the sample pictures instead}';

    protected $description = 'Draw sample pictures for categories, products and the hero / banner / call-to-action sections';

    public function handle(DemoImages $images): int
    {
        if ($this->option('remove')) {
            $this->info($images->remove().' sample picture(s) removed.');

            return self::SUCCESS;
        }
        try {
            $r = $images->load((int) $this->option('products'));
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
        $this->info("Pictures added: {$r['categories']} categories, {$r['products']} products, {$r['sections']} sections.");

        return self::SUCCESS;
    }
}
