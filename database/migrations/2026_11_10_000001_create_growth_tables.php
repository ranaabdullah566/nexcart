<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('body')->nullable();
            $table->json('translations')->nullable();        // {"ur": {"title": "..", "body": ".."}}
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('location', 12);                  // header | footer
            $table->string('label');
            $table->json('translations')->nullable();
            $table->string('type', 12)->default('url');      // url | page | category
            $table->string('url', 300)->nullable();
            $table->foreignId('page_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['location', 'sort_order']);
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 80);
            $table->unsignedTinyInteger('rating');
            $table->string('title', 120)->nullable();
            $table->text('body')->nullable();
            $table->string('status', 12)->default('pending'); // pending | approved | hidden
            $table->text('reply')->nullable();                // the store's public answer
            $table->timestamps();

            $table->unique(['order_id', 'product_id']);       // one review per purchased product
            $table->index(['product_id', 'status']);
        });

        Schema::create('abandoned_checkouts', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('token', 40)->unique();
            $table->json('items');                            // [{product_id, variant_id, options: [ids], qty}]
            $table->decimal('total', 12, 2)->default(0);
            $table->unsignedSmallInteger('items_count')->default(0);
            $table->string('locale', 10)->nullable();
            $table->timestamp('last_seen_at');
            $table->timestamp('reminded_at')->nullable();
            $table->timestamp('recovered_at')->nullable();
            $table->boolean('opted_out')->default(false);
            $table->timestamps();
        });

        Schema::create('loyalty_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('points');                        // + earned / restored, − redeemed / reversed
            $table->string('type', 12);                       // earn | redeem | restore | reverse | adjust
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('locale', 10)->nullable()->after('currency');
            $table->unsignedInteger('points_redeemed')->default(0);
            $table->decimal('points_discount', 12, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn(['locale', 'points_redeemed', 'points_discount']));
        foreach (['loyalty_ledger', 'abandoned_checkouts', 'reviews', 'menu_items', 'pages'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
