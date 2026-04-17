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

    private const MAX_INTRO_HTML_LENGTH = 35000;

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
        $mailDebug = true;
        $mailSubject = $this->content?->title ?: 'Komende activiteiten van Zijpalm';
        $introHtml = $this->content?->text
            ? EditorPhp::make($this->content->text)->toHtml()
            : '<p>Beste leden,</p><p>Hieronder vinden jullie de komende activiteiten van Zijpalm.</p>';

        $introHtml = $this->normalizeIntroLinks($introHtml);
        $introHtml = $this->sanitizeHtmlForAutomate($introHtml);

        try {
            $renderedContent = view('mail.upcoming-activities-digest', [
                'introHtml' => $introHtml,
                'activities' => $this->activities,
                'runningActivities' => $this->runningActivities,
            ])->render();
        } catch (Throwable $exception) {
            Log::error('[UpcomingActivitiesDigest] View render failed, using fallback body', [
                'error' => $exception->getMessage(),
                'activities' => $this->activities->count(),
                'running_activities' => $this->runningActivities->count(),
            ]);

            $renderedContent = '<p>Beste leden,</p><p>De mail met toekomstige activiteiten kon niet volledig worden opgebouwd.</p>';
        }

        $jsonBody = json_encode([
            'emails' => $this->emails,
            'subject' => $mailSubject,
            'body' => $renderedContent,
            'batch_size' => $this->validatedData['batch_size'] ?? config('mail.power_automate.batch_size.default', 50),
            'delay' => $this->validatedData['delay'] ?? config('mail.power_automate.delay.default', 30),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);

        if ($mailDebug) {
            Log::debug('[UpcomingActivitiesDigest] JSON payload prepared', [
                'emails' => $this->emails->count(),
                'activities' => $this->activities->count(),
                'running_activities' => $this->runningActivities->count(),
                'batch_size' => $this->validatedData['batch_size'] ?? config('mail.power_automate.batch_size.default', 50),
                'delay' => $this->validatedData['delay'] ?? config('mail.power_automate.delay.default', 30),
                'json_length' => strlen((string) $jsonBody),
                'subject' => $mailSubject,
            ]);
        }

        if ($jsonBody === false) {
            Log::error('[UpcomingActivitiesDigest] JSON encode failed', [
                'error' => json_last_error_msg(),
                'emails' => $this->emails->count(),
                'activities' => $this->activities->count(),
            ]);

            $jsonBody = json_encode([
                'emails' => $this->emails,
                'subject' => $mailSubject,
                'body' => '<p>Beste leden,</p><p>De mail met toekomstige activiteiten kon niet volledig worden opgebouwd.</p>',
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

    /**
     * Keep digest intro HTML safe and bounded for downstream automation parsers.
     */
    private function sanitizeHtmlForAutomate(string $html): string
    {
        if ($html === '') {
            return '';
        }

        $sanitized = $html;
        $sanitized = preg_replace('/<\s*(script|style|iframe|object|embed)\b[^>]*>.*?<\s*\/\s*\1\s*>/is', '', $sanitized) ?? $sanitized;
        $sanitized = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $sanitized) ?? $sanitized;

        if (mb_strlen($sanitized, 'UTF-8') > self::MAX_INTRO_HTML_LENGTH) {
            Log::warning('[UpcomingActivitiesDigest] Intro truncated for payload safety', [
                'original_length' => mb_strlen($sanitized, 'UTF-8'),
                'max_length' => self::MAX_INTRO_HTML_LENGTH,
            ]);

            $sanitized = mb_substr($sanitized, 0, self::MAX_INTRO_HTML_LENGTH, 'UTF-8');
        }

        return $sanitized;
    }
}
