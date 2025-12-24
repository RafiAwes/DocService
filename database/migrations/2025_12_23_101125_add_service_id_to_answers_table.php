<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function foreignKeyExists(string $table, string $fkName): bool
    {
        $result = DB::select(
            "select constraint_name from information_schema.table_constraints where table_schema = database() and table_name = ? and constraint_type = 'FOREIGN KEY' and constraint_name = ?",
            [$table, $fkName]
        );
        return !empty($result);
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            // Use a proper foreign key column type so the constraint is actually created
            if (!Schema::hasColumn('answers', 'order_id')) {
                $table->foreignId('order_id')
                    ->nullable()
                    ->constrained('orders')
                    ->cascadeOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // If FK exists, drop it by name first (outside the Blueprint try/catch timing issue)
        if ($this->foreignKeyExists('answers', 'answers_order_id_foreign')) {
            Schema::table('answers', function (Blueprint $table) {
                $table->dropForeign('answers_order_id_foreign');
            });
        }

        // Then drop the column if it exists
        if (Schema::hasColumn('answers', 'order_id')) {
            Schema::table('answers', function (Blueprint $table) {
                $table->dropColumn('order_id');
            });
        }
    }
};
