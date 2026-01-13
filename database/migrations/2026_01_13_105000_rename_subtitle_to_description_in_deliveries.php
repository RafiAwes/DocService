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
        if (Schema::hasTable('deliveries')) {
            Schema::table('deliveries', function (Blueprint $table) {
                // Rename subtitle to description
                if (Schema::hasColumn('deliveries', 'subtitle')) {
                    $table->renameColumn('subtitle', 'description');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('deliveries')) {
            Schema::table('deliveries', function (Blueprint $table) {
                if (Schema::hasColumn('deliveries', 'description')) {
                    $table->renameColumn('description', 'subtitle');
                }
            });
        }
    }
};
