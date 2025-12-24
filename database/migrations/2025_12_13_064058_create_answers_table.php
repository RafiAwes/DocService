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
        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_quote_id')->nullable()->constrained('service_quotes')->cascadeOnDelete();
            $table->json('delivery_details_ids')->nullable(); 
            $table->boolean('south_african')->default(false);
            $table->integer('age')->nullable();
            $table->text('about_yourself')->nullable();
            $table->string('birth_certificate')->nullable();
            $table->string('nid_card')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Dropping the table will automatically remove its foreign keys.
        Schema::dropIfExists('answers');
    }
};
