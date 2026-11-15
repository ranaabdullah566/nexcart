<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Categories are one flat level now: sub-categories become ordinary categories (nothing is deleted). */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('categories')->whereNotNull('parent_id')->update(['parent_id' => null]);
    }

    public function down(): void
    {
        // the old parent links are not restorable
    }
};
