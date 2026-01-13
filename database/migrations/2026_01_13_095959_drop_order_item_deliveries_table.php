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
        // Drop the junction table that links order items to delivery details
        Schema::dropIfExists('order_item_deliveries');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate if rolling back
        Schema::create('order_item_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->onDelete('cascade');
            $table->foreignId('delivery_detail_id')->constrained('delivery_details')->onDelete('cascade');
            $table->timestamps();
        });
    }
};
