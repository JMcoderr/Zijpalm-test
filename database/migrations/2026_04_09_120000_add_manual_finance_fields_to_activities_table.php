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
        Schema::table('activities', function (Blueprint $table) {
            $table->json('manual_income_entries')->nullable()->after('free_organizer_count');
            $table->json('manual_expense_entries')->nullable()->after('manual_income_entries');
            $table->decimal('manual_budget', 10, 2)->nullable()->after('manual_expense_entries');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['manual_income_entries', 'manual_expense_entries', 'manual_budget']);
        });
    }
};
