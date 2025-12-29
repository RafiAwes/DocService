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
        if (! Schema::hasTable('answers')) {
            Schema::create('answers', function (Blueprint $table) {
                $table->id();
                // 1. Who answered?
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('order_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('order_item_id')->nullable()->constrained('order_items')->cascadeOnDelete();
                $table->foreignId('cart_id')->nullable()->constrained()->cascadeOnDelete();
                $table->foreignId('cart_item_id')->nullable()->constrained('cart_items')->cascadeOnDelete();
                $table->foreignId('service_quote_id')->nullable()->constrained('service_quotes')->cascadeOnDelete();
                $table->foreignId('questionary_id')->constrained('questionaries')->cascadeOnDelete();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('answers');
    }
};
