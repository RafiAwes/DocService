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
        Schema::table('questionaries', function (Blueprint $table) {
            $table->string('type')->change();
        });

        // 2. Fix Answers Table (Add the Link to Order Items)
        Schema::table('answers', function (Blueprint $table) {
            if (! Schema::hasColumn('answers', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->cascadeOnDelete();
            }
            // Ensure order_id exists before adding order_item_id
            if (! Schema::hasColumn('answers', 'order_id')) {
                $table->foreignId('order_id')->nullable()->after('user_id')->constrained('orders')->cascadeOnDelete();
            }
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
        Schema::table('questionaries', function (Blueprint $table) {
            $table->string('type')->change();
        });        
        Schema::table('answers', function (Blueprint $table) {
            if (Schema::hasColumn('answers', 'order_item_id')) {
                try { $table->dropForeign('answers_order_item_id_foreign'); } catch (\Throwable $e) {}
                $table->dropColumn('order_item_id');
            }
            if (Schema::hasColumn('answers', 'order_id')) {
                try { $table->dropForeign('answers_order_id_foreign'); } catch (\Throwable $e) {}
                $table->dropColumn('order_id');
            }
            if (Schema::hasColumn('answers', 'user_id')) {
                try { $table->dropForeign('answers_user_id_foreign'); } catch (\Throwable $e) {}
                $table->dropColumn('user_id');
            }
        });
    }
};
