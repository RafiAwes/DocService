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
        Schema::table('service_quotes', function (Blueprint $table) {
            $table->json('delivery_details_ids')->nullable()->after('service_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_quotes', function (Blueprint $table) {
            $table->dropColumn('delivery_details_ids');
        });
    }
};
