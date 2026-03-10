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
        $exists = DB::table('contents')->where('name', 'email-betaling-mislukt')->exists();

        if (!$exists) {
            DB::table('contents')->insert([
                'type' => 'email',
                'name' => 'email-betaling-mislukt',
                'title' => 'Betaling mislukt',
                'text' => '{"time":1763000000000,"blocks":[{"type":"paragraph","data":{"text":"Uw betaling is helaas mislukt."}},{"type":"paragraph","data":{"text":"Probeer het alstublieft opnieuw via de betaallink."}}],"version":"2.31.0"}',
                'filePath' => null,
                'fileType' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('contents')->where('name', 'email-betaling-mislukt')->delete();
    }
};