<?php
// This file is part of the app logic and has a short comment so it is easier to read.


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
use Illuminate\Support\Facades\Storage;

class UpcomingActivitiesDigest extends Mailable
{
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
        // Store the data for this mail so the view can use it later.
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
        // Build the subject line for this mail.
        return new Envelope(
            subject: 'AUTOMATE BATCH upcoming_activities_digest',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Pass the values to the Blade template that builds the message body.
        $mailSubject = 'Zijpalm | Komende activiteiten';
            $introHtml = '<p>Beste leden,</p><p>Hieronder vinden jullie de komende activiteiten van Zijpalm.</p>';

            if ($this->content?->text) {
                try {
                    $raw = html_entity_decode((string) $this->content->text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $decoded = json_decode($raw, true);

                    if (is_array($decoded) && array_key_exists('blocks', $decoded)) {
                        $introHtml = EditorPhp::make($raw)->toHtml();
                    } else {
                        $introHtml = '<p>' . e($this->content->text) . '</p>';
                    }
                } catch (Throwable $exception) {
                    Log::warning('[UpcomingActivitiesDigest] introHtml fallback to plain text', [
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
            
        $introHtml = $this->sanitizeIntroHtml($this->normalizeIntroLinks($this->plainTextLinks($introHtml)));
        try {
            $renderedContent = view('mail.upcoming-activities-digest', [
                'user' => null,
                'introHtml' => $introHtml,
                'activities' => $this->activities,
                'runningActivities' => $this->runningActivities,
                'batch_size' => $this->validatedData['batch_size'],
                'delay' => $this->validatedData['delay'],
            ])->render();
        } catch (Throwable $exception) {
            Log::error('[UpcomingActivitiesDigest] Mail view render failed, using fallback body', [
                'error' => $exception->getMessage(),
                'activities' => $this->activities->count(),
                'running_activities' => $this->runningActivities->count(),
            ]);

            $renderedContent = '<p>Beste leden,</p><p>Hieronder vinden jullie de komende activiteiten van Zijpalm.</p>';

            if ($this->runningActivities->isNotEmpty()) {
                $renderedContent .= '<p><strong>Lopende activiteiten:</strong></p><ul>';

                foreach ($this->runningActivities as $activity) {
                    $renderedContent .= '<li><strong>' . e($activity->title) . '</strong><br>';
                    $renderedContent .= 'zijpalm.nl/activiteiten/' . e((string) $activity->id) . '</li>';
                }

                $renderedContent .= '</ul>';
            }

            if ($this->activities->isNotEmpty()) {
                $renderedContent .= '<p><strong>Komende activiteiten:</strong></p><ul>';

                foreach ($this->activities as $activity) {
                    $renderedContent .= '<li><strong>' . e($activity->title) . '</strong><br>';
                    $renderedContent .= e((string) formatDate($activity->start));

                    if (! empty($activity->location)) {
                        $renderedContent .= ' - ' . e($activity->location);
                    }

                    $renderedContent .= '<br>zijpalm.nl/activiteiten/' . e((string) $activity->id) . '</li>';
                }

                $renderedContent .= '</ul>';
            }
        }

        $jsonBody = json_encode([
            'emails' => $this->emails->values()->all(),
            'subject' => $mailSubject,
            'body' => $renderedContent,
            'batch_size' => $this->validatedData['batch_size'],
            'delay' => $this->validatedData['delay'],
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
                'batch_size' => $this->validatedData['batch_size'],
                'delay' => $this->validatedData['delay'],
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
     * Replace local storage image URLs with data URI in the given HTML so images are embedded in mails.
     */
    private function inlineLocalImages(string $html): string
    {
        return preg_replace_callback('/<img\s+[^>]*src=("|\')(.*?)\1[^>]*>/i', function (array $matches) {
            $src = trim($matches[2]);

            if (str_contains($src, '/storage/')) {
                $pos = strpos($src, '/storage/');
                $relative = substr($src, $pos + strlen('/storage/'));
                $path = storage_path('app/public/' . $relative);

                if (is_file($path) && is_readable($path)) {
                    $data = file_get_contents($path);
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $path) ?: 'application/octet-stream';
                    finfo_close($finfo);
                    $b64 = base64_encode($data);
                    return '<img src="data:' . $mime . ';base64,' . $b64 . '"/>';
                }
            }

            return $matches[0];
        }, $html) ?: $html;
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        // Attach files here if this mail needs them.
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
        $sanitized = strip_tags($sanitized, '<p><br><strong><em><b><i><u><ul><ol><li><img><center>') ?? $sanitized;

        // Normalize <img> tags: keep only src and ensure URLs are escaped. Wrap images in <center> for nicer layout in mails.
        $sanitized = preg_replace_callback('/<img\s+[^>]*src=("|\')(.*?)\1[^>]*>/i', function (array $matches) {
            $src = trim($matches[2]);

            // If the source is a relative path (starts with /), convert to absolute URL via url().
            if (str_starts_with($src, '/')) {
                $src = url($src);
            }

            // Only allow http(s) or data URIs; otherwise strip the image.
            if (preg_match('/^(https?:\/\/|data:image\/)/i', $src)) {
                return '<center><img src="' . e($src) . '"></center>';
            }

            return '';
        }, $sanitized) ?? $sanitized;

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
