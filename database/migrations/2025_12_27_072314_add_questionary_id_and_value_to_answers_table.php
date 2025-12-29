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

        return ! empty($result);
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            // Add questionary_id column (nullable) if missing, without FK yet
            if (!Schema::hasColumn('answers', 'questionary_id')) {
                $table->unsignedBigInteger('questionary_id')->nullable()->after('order_item_id');
            }

            // Add value column if it doesn't exist
            if (!Schema::hasColumn('answers', 'value')) {
                $table->text('value')->nullable()->after('questionary_id');
            }
        });

        // Clean up any dangling questionary references before adding FK
        if (Schema::hasColumn('answers', 'questionary_id') && Schema::hasTable('questionaries')) {
            // Ensure column is nullable to allow cleanup
            DB::statement('ALTER TABLE answers MODIFY questionary_id BIGINT UNSIGNED NULL');

            DB::table('answers')
                ->whereNotNull('questionary_id')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('questionaries')
                        ->whereColumn('questionaries.id', 'answers.questionary_id');
                })
                ->update(['questionary_id' => null]);
        }

        // Add FK if not already present
        if (Schema::hasColumn('answers', 'questionary_id') && Schema::hasTable('questionaries') && ! $this->foreignKeyExists('answers', 'answers_questionary_id_foreign')) {
            Schema::table('answers', function (Blueprint $table) {
                $table->foreign('questionary_id')->references('id')->on('questionaries')->cascadeOnDelete();
            });
        }
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
                try { $table->dropForeign('answers_questionary_id_foreign'); } catch (\Throwable $e) {}
                $table->dropColumn('questionary_id');
            }
        });
    }
};
