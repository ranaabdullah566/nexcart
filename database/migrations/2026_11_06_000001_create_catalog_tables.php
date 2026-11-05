<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->json('translations')->nullable();   // {"ur": {"name": "..", "description": ".."}, "ur_Latn": {...}}
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->json('translations')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Admin-defined product fields, e.g. pharmacy: "Composition"; fastfood: "Spice level".
        Schema::create('catalog_attributes', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();
            $table->string('label');
            $table->json('translations')->nullable();
            $table->string('type', 16)->default('text');      // text | number | select | boolean
            $table->json('options')->nullable();              // select choices
            $table->json('business_types')->nullable();       // null = every business
            $table->boolean('is_required')->default(false);
            $table->boolean('is_filterable')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku', 64)->nullable()->unique();
            $table->string('type', 16)->default('simple');     // simple | variable
            $table->string('status', 16)->default('draft');    // draft | active | archived
            $table->text('short_description')->nullable();
            $table->text('description')->nullable();
            $table->json('translations')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('compare_price', 12, 2)->nullable();
            $table->decimal('cost', 12, 2)->nullable();
            $table->boolean('track_stock')->default(true);
            $table->integer('stock_qty')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(5);
            $table->boolean('requires_prescription')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->json('specs')->nullable();                 // {attribute_key: value}
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'category_id']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');                            // "Large / Red"
            $table->string('sku', 64)->nullable()->unique();
            $table->decimal('price', 12, 2);
            $table->decimal('compare_price', 12, 2)->nullable();
            $table->integer('stock_qty')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Add-ons such as "Extra cheese", "Size" (fastfood).
        Schema::create('modifier_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('translations')->nullable();
            $table->unsignedSmallInteger('min_select')->default(0);
            $table->unsignedSmallInteger('max_select')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('modifier_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modifier_group_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('translations')->nullable();
            $table->decimal('price_delta', 12, 2)->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('modifier_group_product', function (Blueprint $table) {
            $table->foreignId('modifier_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['modifier_group_id', 'product_id']);
        });

        // Pharmacy: stock received in batches with an expiry date. Sold first-expiry-first-out.
        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('batch_no', 64);
            $table->date('expires_at')->nullable();
            $table->integer('qty');
            $table->decimal('cost', 12, 2)->nullable();
            $table->timestamps();

            $table->index(['product_id', 'expires_at']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('product_batches')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('change');                         // + received, - removed
            $table->integer('balance');                        // stock after this movement
            $table->string('reason', 32);                      // received | sold | adjustment | damaged | expired | return
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        foreach (['stock_movements', 'product_batches', 'modifier_group_product', 'modifier_options', 'modifier_groups', 'product_variants', 'products', 'catalog_attributes', 'brands', 'categories'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
