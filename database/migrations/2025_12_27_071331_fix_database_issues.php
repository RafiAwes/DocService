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
            if (! Schema::hasColumn('answers', 'order_item_id')) {
                $table->foreignId('order_item_id')->nullable()->after('order_id')->constrained('order_items')->cascadeOnDelete();
            }
            // Ensure order_id exists too
            if (! Schema::hasColumn('answers', 'order_id')) {
                $table->foreignId('order_id')->nullable()->after('user_id')->constrained('orders')->cascadeOnDelete();
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
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');

            $table->dropForeign(['order_item_id']);
            $table->dropColumn('order_item_id');

            $table->dropForeign(['order_id']);
            $table->dropColumn('order_id');
        });
    }
};
