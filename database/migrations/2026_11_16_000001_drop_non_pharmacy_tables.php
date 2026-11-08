<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/** NexCart is pharmacy-only: add-ons (modifiers) and loyalty points are gone. Data in these tables is discarded. */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['modifier_group_product', 'modifier_options', 'modifier_groups', 'loyalty_ledger'] as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        // intentionally empty — the features were removed
    }
};
