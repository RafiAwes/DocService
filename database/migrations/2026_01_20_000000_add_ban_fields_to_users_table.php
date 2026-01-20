<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('ban_type')->nullable()->after('reset_token_verified_at');
            $table->timestamp('banned_until')->nullable()->after('ban_type');
            $table->string('ban_reason')->nullable()->after('banned_until');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['ban_type', 'banned_until', 'ban_reason']);
        });
    }
};
