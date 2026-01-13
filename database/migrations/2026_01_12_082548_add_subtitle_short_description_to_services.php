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
        Schema::table('services', function (Blueprint $table) 
        {
            if (! Schema::hasColumn('services', 'short_description')) {
                $table->string('short_description')->nullable();
            }
            if (! Schema::hasColumn('services', 'hiw_title')) {
                $table->string('hiw_title')->nullable(); // how it works title
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            
            if (Schema::hasColumn('services', 'short_description')) {
                $table->dropColumn('short_description');
            }
            if (Schema::hasColumn('services', 'hiw_title')) {
                $table->dropColumn('hiw_title');
            }
        });
    }
};
