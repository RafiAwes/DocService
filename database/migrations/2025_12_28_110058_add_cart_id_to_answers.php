<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Check if a foreign key exists on a table.
     */
    private function foreignKeyExists(string $table, string $fkName): bool
    {
        $result = DB::select(
            "select constraint_name from information_schema.table_constraints where table_schema = database() and table_name = ? and constraint_type = 'FOREIGN KEY' and constraint_name = ?",
            [$table, $fkName]
        );

        return ! empty($result);
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            // Add columns only if they do not already exist to avoid duplicate-column errors
            if (! Schema::hasColumn('answers', 'cart_id')) {
                $table->string('cart_id')->nullable()->after('order_id');
            }

            if (! Schema::hasColumn('answers', 'cart_item_id')) {
                // Add as plain column first; FK added later when cart_items table is confirmed
                $table->unsignedBigInteger('cart_item_id')->nullable()->after('cart_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            if (Schema::hasColumn('answers', 'cart_item_id')) {
                $table->dropColumn('cart_item_id');
            }

            if (Schema::hasColumn('answers', 'cart_id')) {
                $table->dropColumn('cart_id');
            }
        });
    }
};
