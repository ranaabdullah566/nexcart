<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 40)->nullable()->after('email');
            $table->json('default_address')->nullable()->after('phone');   // remembered for the next checkout
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('type', 16);                           // percent | fixed | free_shipping
            $table->decimal('value', 12, 2)->default(0);
            $table->decimal('min_subtotal', 12, 2)->nullable();
            $table->decimal('max_discount', 12, 2)->nullable();   // cap for percent coupons
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('cities')->nullable();                   // null/empty = every city not claimed by another zone
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_zone_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('free_over', 12, 2)->nullable();      // free when subtotal reaches this
            $table->decimal('min_subtotal', 12, 2)->nullable();   // not offered below this
            $table->string('eta', 60)->nullable();                // "30–45 min", "2–3 days"
            $table->boolean('is_pickup')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 24)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('public_token', 40)->unique();         // guests open their order with this
            $table->string('status', 24)->default('pending');
            $table->string('payment_status', 16)->default('unpaid');
            $table->string('payment_method', 24);
            $table->string('payment_reference')->nullable();
            $table->string('shipping_method');
            $table->boolean('is_pickup')->default(false);
            $table->string('name');
            $table->string('email');
            $table->string('phone', 40);
            $table->string('address_line')->nullable();
            $table->string('city')->nullable();
            $table->string('area')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->text('notes')->nullable();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->boolean('tax_inclusive')->default(false);
            $table->string('currency', 3);
            $table->string('coupon_code', 40)->nullable();
            $table->boolean('requires_prescription')->default(false);
            $table->timestamp('placed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'placed_at']);
            $table->index('email');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('name');                               // snapshots: later edits never rewrite history
            $table->string('variant_name')->nullable();
            $table->string('sku', 64)->nullable();
            $table->json('options')->nullable();                  // [{"name": "Extra cheese", "price": 80}]
            $table->decimal('unit_price', 12, 2);
            $table->unsignedInteger('qty');
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });

        Schema::create('order_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 24);                           // placed | status | payment | note
            $table->string('status', 24)->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['order_events', 'order_items', 'orders', 'shipping_rates', 'shipping_zones', 'coupons'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['phone', 'default_address']));
    }
};
