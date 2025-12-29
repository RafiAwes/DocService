<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the type column enum to have correct values
        if (Schema::hasColumn('services', 'type')) {
            DB::statement("ALTER TABLE services MODIFY type ENUM('Quote', 'Checkout') NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('services', 'type')) {
            DB::statement("ALTER TABLE services MODIFY type ENUM('Quote', 'Check box') NULL");
        }
    }
};
