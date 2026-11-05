<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_slots', function (Blueprint $table) {
            $table->id();
            $table->string('label', 60);
            $table->string('starts_at', 5);                        // "09:00"
            $table->string('ends_at', 5);                          // "12:00"
            $table->unsignedInteger('capacity')->nullable();       // orders per day, null = unlimited
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('shipping_rates', function (Blueprint $table) {
            $table->unsignedSmallInteger('eta_minutes')->nullable()->after('eta');   // drives the estimated arrival time
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('rider_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->timestamp('rider_assigned_at')->nullable();
            $table->timestamp('eta_at')->nullable();
            $table->string('prescription_status', 16)->nullable();   // pending | approved | rejected
            $table->string('prescription_note')->nullable();
            $table->foreignId('prescription_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('prescription_reviewed_at')->nullable();
            $table->date('slot_date')->nullable();
            $table->foreignId('delivery_slot_id')->nullable()->constrained('delivery_slots')->nullOnDelete();
            $table->string('slot_label', 80)->nullable();            // snapshot, e.g. "Morning 09:00–12:00"

            $table->index(['slot_date', 'delivery_slot_id']);
            $table->index(['rider_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rider_id');
            $table->dropConstrainedForeignId('prescription_reviewed_by');
            $table->dropConstrainedForeignId('delivery_slot_id');
            $table->dropColumn(['rider_assigned_at', 'eta_at', 'prescription_status', 'prescription_note', 'prescription_reviewed_at', 'slot_date', 'slot_label']);
        });
        Schema::table('shipping_rates', fn (Blueprint $t) => $t->dropColumn('eta_minutes'));
        Schema::dropIfExists('delivery_slots');
    }
};
