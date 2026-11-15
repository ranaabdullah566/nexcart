<?php

use App\Models\HomeSection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The Brand module is gone: products no longer have a brand and the brands table is dropped. Home "brands strip" sections are removed too. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('home_sections')) {
            HomeSection::withTrashed()->where('type', 'brands')->get()->each->forceDelete();
        }
        if (Schema::hasColumn('products', 'brand_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropConstrainedForeignId('brand_id');
            });
        }
        Schema::dropIfExists('brands');
    }

    public function down(): void
    {
        // not restored: the module was removed on purpose
    }
};
