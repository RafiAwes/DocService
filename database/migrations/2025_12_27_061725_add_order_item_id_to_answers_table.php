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
        Schema::table('answers', function (Blueprint $table) {
            // Add user_id if missing
            if (! Schema::hasColumn('answers', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->cascadeOnDelete();
            }

            // Ensure order_id exists before adding order_item_id
            if (! Schema::hasColumn('answers', 'order_id')) {
                // place near the top if missing so dependent columns can reference it
                $table->foreignId('order_id')->nullable()->after('user_id')->constrained('orders')->cascadeOnDelete();
            }

            // Add order_item_id if missing. If order_id is still missing for any reason, avoid the 'after' clause.
            if (! Schema::hasColumn('answers', 'order_item_id')) {
                if (Schema::hasColumn('answers', 'order_id')) {
                    $table->foreignId('order_item_id')->nullable()->after('order_id')->constrained('order_items')->cascadeOnDelete();
                } else {
                    $table->foreignId('order_item_id')->nullable()->after('user_id')->constrained('order_items')->cascadeOnDelete();
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            if (Schema::hasColumn('answers', 'order_item_id')) {
                try {
                    $table->dropForeign('answers_order_item_id_foreign');
                } catch (\Throwable $e) {
                    // ignore if FK missing
                }
                $table->dropColumn('order_item_id');
            }

            if (Schema::hasColumn('answers', 'order_id')) {
                try {
                    $table->dropForeign('answers_order_id_foreign');
                } catch (\Throwable $e) {
                    // ignore if FK missing
                }
                $table->dropColumn('order_id');
            }

            if (Schema::hasColumn('answers', 'user_id')) {
                try {
                    $table->dropForeign('answers_user_id_foreign');
                } catch (\Throwable $e) {
                    // ignore if FK missing
                }
                $table->dropColumn('user_id');
            }
        });
    }
};
