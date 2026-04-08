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
        $oldUrl = 'https://82393.afasinsite.nl/portal-insite-prs/tp-personeelsverenigingen';
        $newUrl = 'https://82393.afasinsite.nl/portal-insite-prs/tp-personeelsverenigingen?tab=zijpalm';

        $content = DB::table('contents')->where('name', 'lid-worden-info')->first();

        if ($content !== null && str_contains($content->text, $oldUrl)) {
            DB::table('contents')
                ->where('name', 'lid-worden-info')
                ->update([
                    'text' => str_replace($oldUrl, $newUrl, $content->text),
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $oldUrl = 'https://82393.afasinsite.nl/portal-insite-prs/tp-personeelsverenigingen';
        $newUrl = 'https://82393.afasinsite.nl/portal-insite-prs/tp-personeelsverenigingen?tab=zijpalm';

        $content = DB::table('contents')->where('name', 'lid-worden-info')->first();

        if ($content !== null && str_contains($content->text, $newUrl)) {
            DB::table('contents')
                ->where('name', 'lid-worden-info')
                ->update([
                    'text' => str_replace($newUrl, $oldUrl, $content->text),
                    'updated_at' => now(),
                ]);
        }
    }
};