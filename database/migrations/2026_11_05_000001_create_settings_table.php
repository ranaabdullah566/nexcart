<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            // store_id is nullable now (single-tenant); keeps the door open for multi-tenant later.
            $table->unsignedBigInteger('store_id')->nullable()->index();
            $table->string('group', 64)->index();
            $table->string('key', 128);
            $table->json('value')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'group', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
