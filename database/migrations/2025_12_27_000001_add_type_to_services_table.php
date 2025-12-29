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
        Schema::table('services', function (Blueprint $table) {
            // Add column only if it doesn't exist
            if (!Schema::hasColumn('services', 'type')) {
                $table->enum('type', ['Quote', 'Checkout'])->nullable()->after('order_type');
            }
        });

        // If column exists but has wrong enum values, update it
        if (Schema::hasColumn('services', 'type')) {
            // Modify the column to ensure it has the correct enum values
            Schema::table('services', function (Blueprint $table) {
                $table->enum('type', ['Quote', 'Checkout'])->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};

