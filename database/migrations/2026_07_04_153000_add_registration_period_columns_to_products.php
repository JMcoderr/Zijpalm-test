<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->date('registration_starts_at')->nullable()->after('allow_guest_checkout');
            $table->date('registration_ends_at')->nullable()->after('registration_starts_at');
        });

        DB::table('products')
            ->whereNotNull('signup_deadline')
            ->update([
                'registration_ends_at' => DB::raw('DATE(signup_deadline)'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['registration_starts_at', 'registration_ends_at']);
        });
    }
};
