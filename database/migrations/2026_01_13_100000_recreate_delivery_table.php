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
        // Drop old delivery_details table if it exists
        if (Schema::hasTable('delivery_details')) {
            Schema::dropIfExists('delivery_details');
        }

        // Create new deliveries table
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // e.g., "Standard Delivery", "Express Delivery"
            $table->string('subtitle')->nullable(); // e.g., "3-5 business days"
            $table->decimal('price', 10, 2); // Delivery cost
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
