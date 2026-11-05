<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Home page = an ordered list of sections the admin can add, edit, hide and reorder.
        Schema::create('home_sections', function (Blueprint $table) {
            $table->id();
            $table->string('type', 24);                 // hero | categories | products | banner | features | text
            $table->string('title')->nullable();
            $table->json('translations')->nullable();   // {"ur": {"title": "..", "subtitle": ".."}, ...}
            $table->json('settings')->nullable();       // type specific
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_sections');
    }
};
