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
            ->where('name', 'email-nieuwe-activiteit')
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
            ->where('name', 'email-nieuwe-activiteit')
            ->update([
                'text' => '{"time":1750075440441,"blocks":[{"id":"xnNwYZd4RN","type":"paragraph","data":{"text":"De volgende activiteit is aangemaakt!"}}],"version":"2.31.0-rc.7"}',
                'updated_at' => now(),
            ]);
    }
};