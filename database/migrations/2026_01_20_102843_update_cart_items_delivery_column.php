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
        Schema::table('cart_items', function (Blueprint $table) {
            if (Schema::hasColumn('cart_items', 'delivery_details_ids')) {
                $table->dropColumn('delivery_details_ids');
            }

            if (! Schema::hasColumn('cart_items', 'delivery_id')) {
                $table->foreignId('delivery_id')
                    ->nullable()
                    ->after('quantity')
                    ->constrained('deliveries')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            if (Schema::hasColumn('cart_items', 'delivery_id')) {
                $table->dropForeign(['delivery_id']);
                $table->dropColumn('delivery_id');
            }

            if (! Schema::hasColumn('cart_items', 'delivery_details_ids')) {
                $table->json('delivery_details_ids')->nullable()->after('quantity');
            }
        });
    }
};
