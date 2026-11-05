<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Everything an admin can delete moves to the Recycle Bin first (products already did). */
    private const TABLES = [
        'categories', 'brands', 'catalog_attributes', 'modifier_groups', 'coupons', 'shipping_zones', 'delivery_slots',
        'home_sections', 'pages', 'menu_items', 'reviews', 'users',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->softDeletes());
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropSoftDeletes());
        }
    }
};
