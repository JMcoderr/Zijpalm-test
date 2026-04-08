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
        $targetUrl = 'https://82393.afasinsite.nl/portal-insite-prs/tp-personeelsverenigingen?tab=Zijpalm#Zijpalm';

        $possibleUrls = [
            'https://82393.afasinsite.nl/portal-insite-prs/tp-personeelsverenigingen',
            'https://82393.afasinsite.nl/portal-insite-prs/tp-personeelsverenigingen?tab=zijpalm',
            'https://82393.afasinsite.nl/portal-insite-prs/tp-personeelsverenigingen?tab=Zijpalm',
            'https://82393.afasinsite.nl/portal-insite-prs/tp-personeelsverenigingen?tab=zijpalm#zijpalm',
        ];

        $content = DB::table('contents')->where('name', 'lid-worden-info')->first();

        if ($content === null || $content->text === null) {
            return;
        }

        $updatedText = $content->text;

        foreach ($possibleUrls as $url) {
            $updatedText = str_replace($url, $targetUrl, $updatedText);
        }

        if ($updatedText !== $content->text) {
            DB::table('contents')
                ->where('name', 'lid-worden-info')
                ->update([
                    'text' => $updatedText,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $targetUrl = 'https://82393.afasinsite.nl/portal-insite-prs/tp-personeelsverenigingen?tab=Zijpalm#Zijpalm';
        $fallbackUrl = 'https://82393.afasinsite.nl/portal-insite-prs/tp-personeelsverenigingen?tab=zijpalm';

        $content = DB::table('contents')->where('name', 'lid-worden-info')->first();

        if ($content !== null && $content->text !== null && str_contains($content->text, $targetUrl)) {
            DB::table('contents')
                ->where('name', 'lid-worden-info')
                ->update([
                    'text' => str_replace($targetUrl, $fallbackUrl, $content->text),
                    'updated_at' => now(),
                ]);
        }
    }
};