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
            // Add questionary_id if it doesn't exist
            if (!Schema::hasColumn('answers', 'questionary_id')) {
                $table->foreignId('questionary_id')
                    ->after('order_item_id')
                    ->constrained('questionaries')
                    ->cascadeOnDelete();
            }
            
            // Add value column if it doesn't exist
            if (!Schema::hasColumn('answers', 'value')) {
                $table->text('value')->nullable()->after('questionary_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            if (Schema::hasColumn('answers', 'value')) {
                $table->dropColumn('value');
            }
            
            if (Schema::hasColumn('answers', 'questionary_id')) {
                $table->dropForeign(['questionary_id']);
                $table->dropColumn('questionary_id');
            }
        });
    }
};
