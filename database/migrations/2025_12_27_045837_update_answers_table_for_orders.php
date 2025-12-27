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
            // 1. Add order_id (Nullable)
            $table->foreignId('order_id')->nullable()->after('id')->constrained('orders')->cascadeOnDelete();

            // 2. Make service_quote_id Nullable (because Order answers won't have it)
            $table->foreignId('service_quote_id')->nullable()->change();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropColumn('order_id');
            $table->foreignId('service_quote_id')->nullable(false)->change();
        });
    }
};
