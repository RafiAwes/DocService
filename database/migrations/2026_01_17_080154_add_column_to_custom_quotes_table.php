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
        Schema::table('custom_quotes', function (Blueprint $table) {
            $table->string('status')->after('residence_country')->default(value: 'New'); //New, Mailed
            $table->longText('reply')->after('status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('custom_quotes', function (Blueprint $table) {
            $table->dropColumn(['status','reply']);   
        });
    }
};
