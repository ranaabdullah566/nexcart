<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Everything a product sheet (e.g. the Foodpanda export) carries is stored, and every upload is logged. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('barcodes')->nullable();                         // up to 5 (or more) barcodes
            $table->unsignedSmallInteger('max_per_order')->nullable();   // "maximumSalesQuantity"
            $table->boolean('is_weighted')->default(false);
            $table->decimal('base_weight', 12, 3)->nullable();
            $table->string('base_weight_unit', 16)->nullable();
            $table->decimal('price_per_base_unit', 12, 2)->nullable();
            $table->string('source', 30)->nullable()->index();            // 'sheet' when a sync created or last updated it
            $table->string('source_hash', 40)->nullable();                // fingerprint of the last synced row: unchanged rows are skipped
            $table->json('source_data')->nullable();                      // extra categories and any unknown columns
            $table->timestamp('synced_at')->nullable();
        });

        Schema::create('product_syncs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('filename', 190);
            $table->unsignedInteger('rows')->default(0);
            $table->unsignedInteger('created')->default(0);
            $table->unsignedInteger('updated')->default(0);
            $table->unsignedInteger('unchanged')->default(0);
            $table->unsignedInteger('skipped')->default(0);
            $table->unsignedInteger('deactivated')->default(0);
            $table->unsignedInteger('categories_created')->default(0);
            $table->json('options')->nullable();
            $table->json('errors')->nullable();
            $table->unsignedInteger('duration_ms')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_syncs');
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['source']);   // the index goes first (SQLite cannot drop an indexed column)
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['barcodes', 'max_per_order', 'is_weighted', 'base_weight', 'base_weight_unit', 'price_per_base_unit', 'source', 'source_hash', 'source_data', 'synced_at']);
        });
    }
};
