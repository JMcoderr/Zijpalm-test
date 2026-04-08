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
        $exists = DB::table('contents')
            ->where('type', 'email')
            ->where('name', 'email-toekomstige-activiteiten-digest')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('contents')->insert([
            'type' => 'email',
            'name' => 'email-toekomstige-activiteiten-digest',
            'title' => 'Email Toekomstige Activiteiten',
            'text' => '{"time":1775600000000,"blocks":[{"type":"paragraph","data":{"text":"Beste leden,"}},{"type":"paragraph","data":{"text":"Hieronder vinden jullie de komende activiteiten van Zijpalm."}}],"version":"2.31.0"}',
            'filePath' => null,
            'fileType' => null,
            'created_at' => now(),
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
            ->where('name', 'email-toekomstige-activiteiten-digest')
            ->delete();
    }
};
