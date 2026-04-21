<?php

namespace App\Mail;

use App\Models\Activity;
use App\Models\Content as ContentModel;
use BumpCore\EditorPhp\EditorPhp;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class UpcomingActivitiesDigest extends Mailable
{
    use SerializesModels;

    public Collection $emails;
    public Collection $activities;
    public Collection $runningActivities;
    public array $validatedData;
    public ?ContentModel $content;

    /**
     * Create a new message instance.
     */
    public function __construct(Collection $emails, Collection $activities, Collection $runningActivities, array $validatedData = [])
    {
        $this->emails = $emails;
        $this->activities = $activities;
        $this->runningActivities = $runningActivities;
        $this->validatedData = $validatedData;
        $this->content = ContentModel::where('name', 'email-toekomstige-activiteiten')->first();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'AUTOMATE BATCH upcoming_activities_digest',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $mailSubject = 'Zijpalm | Komende activiteiten';
        $introHtml = $this->content?->text
            ? EditorPhp::make($this->content->text)->toHtml()
            : '<p>Beste leden,</p><p>Hieronder vinden jullie de komende activiteiten van Zijpalm.</p>';

        $introHtml = $this->sanitizeIntroHtml($this->normalizeIntroLinks($this->plainTextLinks($introHtml)));

        try {
            $renderedContent = view('mail.upcoming-activities-digest', [
                'user' => null,
                'introHtml' => $introHtml,
                'activities' => $this->activities,
                'runningActivities' => $this->runningActivities,
            ])->render();
        } catch (Throwable $exception) {
            Log::error('[UpcomingActivitiesDigest] Mail view render failed, using fallback body', [
                'error' => $exception->getMessage(),
                'activities' => $this->activities->count(),
                'running_activities' => $this->runningActivities->count(),
            ]);

            $renderedContent = '<p>Beste leden,</p><p>Hieronder vinden jullie de komende activiteiten van Zijpalm.</p>';
        }

        $jsonBody = json_encode([
            'emails' => $this->emails->values()->all(),
            'subject' => $mailSubject,
            'body' => $renderedContent,
            'batch_size' => $this->validatedData['batch_size'] ?? config('mail.power_automate.batch_size.default', 50),
            'delay' => $this->validatedData['delay'] ?? config('mail.power_automate.delay.default', 30),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);

        if ($jsonBody === false) {
            Log::error('[UpcomingActivitiesDigest] JSON encode failed', [
                'error' => json_last_error_msg(),
                'emails' => $this->emails->count(),
                'activities' => $this->activities->count(),
                'running_activities' => $this->runningActivities->count(),
            ]);

            $jsonBody = json_encode([
                'emails' => $this->emails->values()->all(),
                'subject' => $mailSubject,
                'body' => '<p>Komende activiteiten van Zijpalm.</p>',
                'batch_size' => $this->validatedData['batch_size'] ?? config('mail.power_automate.batch_size.default', 50),
                'delay' => $this->validatedData['delay'] ?? config('mail.power_automate.delay.default', 30),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) ?: '{}';
        }

        return new Content(
            text: 'mail.raw-json',
            with: [
                'jsonBody' => $jsonBody,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    private function normalizeIntroLinks(string $html): string
    {
        return preg_replace_callback(
            '/href\s*=\s*(["\'])(.*?)\1/i',
            function (array $matches) {
                $quote = $matches[1];
                $href = trim($matches[2]);

                if ($href === '') {
                    return 'href=' . $quote . $href . $quote;
                }

                if (str_starts_with($href, 'www.')) {
                    $href = 'https://' . $href;
                }

                return 'href=' . $quote . e($href) . $quote;
            },
            $html
        ) ?? $html;
    }

    private function sanitizeIntroHtml(string $html): string
    {
        $sanitized = $html;

        // Remove problematic editor classes/attributes for mail clients.
        $sanitized = strip_tags($sanitized, '<p><br><strong><em><b><i><u><ul><ol><li>') ?? $sanitized;
        $sanitized = preg_replace('/\s(?:class|style|id|data-[a-z0-9_-]+|role)=("[^"]*"|\'[^\']*\')/i', '', $sanitized) ?? $sanitized;

        // Normalize pasted non-breaking spaces and reduce excessive blank paragraphs.
        $sanitized = str_replace(["\xC2\xA0", '&nbsp;'], ' ', $sanitized);
        $sanitized = preg_replace('/(<p>\s*<\/p>\s*){2,}/i', '<p></p>', $sanitized) ?? $sanitized;

        return $sanitized;
    }

    private function plainTextLinks(string $html): string
    {
        return preg_replace_callback(
            '/<a\s+[^>]*href=("|\")(.*?)(\1)[^>]*>(.*?)<\/a>/is',
            function (array $matches) {
                $href = trim(html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $label = trim(strip_tags(html_entity_decode($matches[4], ENT_QUOTES | ENT_HTML5, 'UTF-8')));

                if ($href === '') {
                    return $label;
                }

                if ($label === '' || $label === $href) {
                    return e($href);
                }

                return e($label . ' (' . $href . ')');
            },
            $html
        ) ?? $html;
    }
}
