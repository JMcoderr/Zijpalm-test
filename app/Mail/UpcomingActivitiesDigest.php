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
        $mailSubject = $this->content?->title ?: 'Komende activiteiten van Zijpalm';
        $introHtml = $this->content?->text
            ? EditorPhp::make($this->content->text)->toHtml()
            : '<p>Beste leden,</p><p>Hieronder vinden jullie de komende activiteiten van Zijpalm.</p>';

        $introHtml = $this->sanitizeIntroHtml($this->normalizeIntroLinks($introHtml));

        $renderedContent = view('mail.upcoming-activities-digest', [
            'introHtml' => $introHtml,
            'activities' => $this->activities,
            'runningActivities' => $this->runningActivities,
        ])->render();

        $jsonBody = json_encode([
            'emails' => $this->emails,
            'subject' => $mailSubject,
            'body' => $renderedContent,
            'batch_size' => $this->validatedData['batch_size'] ?? config('mail.power_automate.batch_size.default', 50),
            'delay' => $this->validatedData['delay'] ?? config('mail.power_automate.delay.default', 30),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

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
        $sanitized = strip_tags($sanitized, '<p><br><a><strong><em><b><i><u><ul><ol><li>') ?? $sanitized;
        $sanitized = preg_replace('/\s(?:class|style|id|data-[a-z0-9_-]+|role)=("[^"]*"|\'[^\']*\')/i', '', $sanitized) ?? $sanitized;

        // Normalize pasted non-breaking spaces and reduce excessive blank paragraphs.
        $sanitized = str_replace(["\xC2\xA0", '&nbsp;'], ' ', $sanitized);
        $sanitized = preg_replace('/(<p>\s*<\/p>\s*){2,}/i', '<p></p>', $sanitized) ?? $sanitized;

        return $sanitized;
    }
}
