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
        if (! Schema::hasTable('cart_items')) {
            Schema::create('cart_items', function (Blueprint $table) {
                $table->id();
                // Add cart_id without FK if carts table is missing
                if (Schema::hasTable('carts')) {
                    $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
                } else {
                    $table->unsignedBigInteger('cart_id');
                }

                $table->foreignId('service_id')->constrained();
                $table->integer('quantity')->nullable();
                $table->json('delivery_details_ids')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop FK on answers.cart_item_id first to allow dropping cart_items table
        if (Schema::hasTable('answers') && Schema::hasColumn('answers', 'cart_item_id')) {
            $fkExists = DB::select(
                "select constraint_name from information_schema.table_constraints where table_schema = database() and table_name = 'answers' and constraint_type = 'FOREIGN KEY' and constraint_name = 'answers_cart_item_id_foreign'"
            );

            if (! empty($fkExists)) {
                Schema::table('answers', function (Blueprint $table) {
                    $table->dropForeign('answers_cart_item_id_foreign');
                });
            }
        }

        Schema::dropIfExists('cart_items');
    }
};
