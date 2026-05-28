<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('contents')
            ->where('type', 'email')
            ->where('name', 'email-herinnering-activiteit-deelnemers')
            ->update([
                'text' => '',
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('contents')
            ->where('type', 'email')
            ->where('name', 'email-herinnering-activiteit-deelnemers')
            ->update([
                'text' => '{"time":1750075440441,"blocks":[{"id":"xnNwYZd4RN","type":"paragraph","data":{"text":"Een activiteit waarvoor u bent ingeschreven start binnenkort!"}}],"version":"2.31.0-rc.7"}',
                'updated_at' => now(),
            ]);
    }
};