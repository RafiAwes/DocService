<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('how_it_works', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->string('title');
            $table->timestamps();
        });

        // Remove hiw_title from services table if it exists
        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'hiw_title')) {
                $table->dropColumn('hiw_title');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('how_it_works');

        // Re-add hiw_title column if rolling back
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'hiw_title')) {
                $table->string('hiw_title')->nullable();
            }
        });
    }
};
