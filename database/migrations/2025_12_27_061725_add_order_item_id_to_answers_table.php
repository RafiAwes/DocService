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
        Schema::table('answers', function (Blueprint $table) {
            // 1. Link to User (Who answered?)
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->cascadeOnDelete();

            // 2. Link to Specific Line Item (The new parent)
            $table->foreignId('order_item_id')->nullable()->after('order_id')->constrained('order_items')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            //
        });
    }
};
