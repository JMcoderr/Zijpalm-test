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
        Schema::table('users', function (Blueprint $table) {
            $table->string('emailSecondary')->nullable()->unique()->after('email');
            $table->string('emailTertiary')->nullable()->unique()->after('emailSecondary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['emailSecondary']);
            $table->dropUnique(['emailTertiary']);
            $table->dropColumn(['emailSecondary', 'emailTertiary']);
        });
    }
};