<?php
// This file is part of the app logic and has a short comment so it is easier to read.


namespace App\Mail;

use App\ApplicationStatus;
use App\Models\Activity;
use App\Models\Application;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Content as ContentModel;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use DOMDocument;
use DOMXPath;
use Throwable;
use Illuminate\Support\Facades\Storage;

class ActivityApplied extends Mailable
{
    public User $user;
    public Activity $activity;
    public ?Application $application;
    public ContentModel $content;
    public ContentModel $reserveContent;
    public $qrcode;
    public bool $reserve;
    public bool $forceDefaultTemplate;

    /**
     * Keep payload size bounded for downstream automation parsers.
     */
    private const MAX_PERSONAL_CONFIRMATION_LENGTH = 35000;

    /**
     * Create a new message instance.
     */
    public function __construct(Activity $activity, User $user, bool $reserve = false, bool $forceDefaultTemplate = false)
    {
        // Store the data for this mail so the view can use it later.
        // Save the activity and user so the mail can be built with the right data.
        $this->activity = $activity;
        $this->user = $user;
        $this->reserve = $reserve;
        $this->forceDefaultTemplate = $forceDefaultTemplate;

        // Load the normal and reserve mail content from the database cache.
        $this->content = ContentModel::where('name', 'email-activiteit-aangemeld')->first();
        $this->reserveContent = ContentModel::where('name', 'email-activiteit-aangemeld-reserve')->first();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        // Build the subject line for this mail.
        // The subject is a fixed label because the mail is used for automation.
        return new Envelope(
            subject: $this->content->title ?? 'AUTOMATE SINGLE activity_applied',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Pass the values to the Blade template that builds the message body.
        // Generate the QR code for the WhatsApp URL when the activity has one.
        if (isset($this->activity->whatsappUrl)) {
            $this->qrcode = (string) QrCode::size(192)->format('png')->generate($this->activity->whatsappUrl);
        }

        // Clean up the mail content first so the HTML stays safe and predictable.

        $defaultContentHtml = $this->sanitizeMailHtml((string) ($this->content->textHTML ?? ''));
        $defaultContentHtml = $this->stripGenericGreeting($defaultContentHtml);
        if (empty($defaultContentHtml) || !$this->isValidHtml($defaultContentHtml)) {
            Log::error('[ActivityApplied] Invalid defaultContentHtml from Editor.js, using fallback.');
            $defaultContentHtml = '';
        }


        $reserveContentHtml = $this->sanitizeMailHtml((string) ($this->reserveContent->textHTML ?? ''));
        $reserveContentHtml = $this->stripGenericGreeting($reserveContentHtml);
        if (empty($reserveContentHtml) || !$this->isValidHtml($reserveContentHtml)) {
            Log::error('[ActivityApplied] Invalid reserveContentHtml from Editor.js, using fallback.');
            $reserveContentHtml = '';
        }

        $personalConfirmationHtml = null;
        if ($this->activity->personal_confirmation_enabled) {
            try {
                $personalConfirmationHtml = $this->sanitizeMailHtml((string) $this->activity->personalConfirmationHTML);
                $personalConfirmationHtml = $this->stripGenericGreeting($personalConfirmationHtml);
                if (empty($personalConfirmationHtml) || !$this->isValidHtml($personalConfirmationHtml)) {
                    Log::error('[ActivityApplied] Invalid personalConfirmationHtml from Editor.js, using fallback.');
                    $personalConfirmationHtml = '';
                }
            } catch (Throwable $exception) {
                Log::error('[ActivityApplied] Personal confirmation render failed, falling back to default content', [
                    'activity_id' => $this->activity->id,
                    'user_id' => $this->user->id,
                    'error' => $exception->getMessage(),
                ]);
                $personalConfirmationHtml = '';
            }
        }

        // Get the application record for this user and activity.
        $this->application = $this->activity->applications()
            ->where('user_id', $this->user->id)
            ->whereNot('status', ApplicationStatus::Cancelled)
            ->first();

        try {
            // If admin provided a full custom mail body, use it with token replacements.
            if ($this->content && trim((string) $this->content->text) !== '') {
                $renderedContent = $this->content->mailHtml([
                    'activity_title' => $this->activity->title,
                    'activity_location' => (string) $this->activity->location,
                    'activity_start' => formatDate($this->activity->start) . ' om ' . formatTime($this->activity->start),
                    'participants' => $this->application ? (int) $this->application->participants : 0,
                ]);
            } else {
                // Render the normal Blade mail template first.
                $renderedContent = view('mail.activity-applied', [
                    'activity' => $this->activity,
                    'application' => $this->application,
                    'user' => $this->user,
                    'content' => $this->content,
                    'reserveContent' => $this->reserveContent,
                    'defaultContentHtml' => $defaultContentHtml,
                    'reserveContentHtml' => $reserveContentHtml,
                    'qrcode' => $this->qrcode,
                    'reserve' => $this->reserve,
                    'personalConfirmationHtml' => $personalConfirmationHtml,
                ])->render();
            }
        } catch (Throwable $exception) {
            // If the template fails, fall back to a very simple HTML body.
            Log::error('[ActivityApplied] Mail view render failed, using fallback body', [
                'activity_id' => $this->activity->id,
                'user_id' => $this->user->id,
                'error' => $exception->getMessage(),
            ]);

            $introHtml = $this->reserve
                ? $reserveContentHtml
                : ($personalConfirmationHtml ?: $defaultContentHtml);

            $applicationSummary = $this->application
                ? '<p><strong>Deelnemers:</strong> ' . (int) $this->application->participants . '</p>'
                : '';

            $renderedContent =
                $introHtml .
                '<p><strong>Activiteit:</strong> ' . e($this->activity->title) . '</p>' .
                '<p><strong>Locatie:</strong> ' . e((string) $this->activity->location) . '</p>' .
                '<p><strong>Start:</strong> ' . e(formatDate($this->activity->start)) . ' om ' . e(formatTime($this->activity->start)) . ' uur</p>' .
                $applicationSummary;
        }

        // Inline any local storage images into the rendered HTML so they are embedded in outgoing mails.
        $renderedContent = $this->inlineLocalImages($renderedContent);

        // Send the rendered HTML to the automation system as JSON.
        $jsonBody = json_encode([
            'email' => $this->user->email,
            'subject' => $this->content->title . ' ' . $this->activity->title,
            'body' => $renderedContent,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);

        if ($jsonBody === false) {
            // If JSON encoding fails, fall back to an empty body instead of breaking the mail.
            Log::error('[ActivityApplied] JSON encode failed', [
                'activity_id' => $this->activity->id,
                'user_id' => $this->user->id,
                'error' => json_last_error_msg(),
            ]);

            $jsonBody = json_encode([
                'email' => $this->user->email,
                'subject' => $this->content->title . ' ' . $this->activity->title,
                'body' => '',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) ?: '{}';
        }

        return new Content(
            text: 'mail.raw-json',
            with: [
                'jsonBody' => $jsonBody
            ],
        );
    }

    /**
     * Checks if the given HTML contains at least one valid HTML tag and is not just plain text.
     */
    private function isValidHtml(string $html): bool
    {
        // Basic check: must contain at least one HTML tag and not be just plain text
        return preg_match('/<([a-z][\w-]*)(?:\s[^>]*)?>.*?<\/\1>/is', $html) === 1;
    }

    /**
     * Remove risky tags/control bytes and keep HTML within a safe size for transport.
     */
    private function sanitizeMailHtml(string $html): string
    {
        if ($html === '') {
            return '';
        }

        $sanitized = $html;

        // Normalize non-breaking spaces from copy/paste content (Word/Outlook).
        $sanitized = str_replace(["\xC2\xA0", '&nbsp;'], ' ', $sanitized);

        // Keep the markup simple so Power Automate only receives basic, predictable HTML.
        $sanitized = strip_tags($sanitized, '<p><br><a><strong><em><b><i><u><ul><ol><li><img><center>') ?? $sanitized;

        // Remove editor-specific and styling attributes that can make the payload fragile, but keep src on <img>.
        $sanitized = preg_replace_callback('/<img\s+([^>]*)>/i', function (array $matches) {
            $attrs = $matches[1];
            // Keep only src attribute from img, discard others.
            if (preg_match('/src=("|\')(.*?)\1/i', $attrs, $m)) {
                $src = $m[2];
                return '<center><img src="' . e($src) . '"></center>';
            }
            return ''; 
        }, $sanitized) ?? $sanitized;

        // Remove other styling attributes globally.
        $sanitized = preg_replace('/\s(?:class|style|id|data-[a-z0-9_-]+|role)=("[^"]*"|\'[^\']*\')/i', '', $sanitized) ?? $sanitized;

        // Keep anchors clickable, but strip any non-essential attributes.
        $sanitized = preg_replace('/<a\s+([^>]*?)href=("[^"]*"|\'[^\']*\')([^>]*)>/i', '<a href=$2>', $sanitized) ?? $sanitized;
        $sanitized = preg_replace('/<a\s+href=("[^"]*"|\'[^\']*\')\s*>/i', '<a href=$1>', $sanitized) ?? $sanitized;

        // If a link starts with www., make it absolute for mail clients.
        $sanitized = preg_replace_callback('/<a\s+href=("|\')(www\.[^"\']+)\1>/i', function (array $matches) {
            return '<a href="https://' . $matches[2] . '">';
        }, $sanitized) ?? $sanitized;

        // Strip tags that can break downstream rendering or automation parsers.
        $sanitized = preg_replace('/<\s*(script|style|iframe|object|embed)\b[^>]*>.*?<\s*\/\s*\1\s*>/is', '', $sanitized) ?? $sanitized;

        // Remove non-printable control characters except line breaks and tabs.
        $sanitized = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $sanitized) ?? $sanitized;

        // Prevent excessive empty spacing in clients when content has many empty paragraphs.
        $sanitized = preg_replace('/(<p>\s*<\/p>\s*){2,}/i', '<p></p>', $sanitized) ?? $sanitized;

        // Convert bare URLs in text nodes into explicit links for stable mail rendering.
        $sanitized = $this->linkifyTextUrls($sanitized);

        // Some mail security filters silently block external links; obfuscate HTTPS links to preserve deliverability.
        $sanitized = $this->obfuscateHttpsUrls($sanitized);

        // Avoid oversized payloads in Power Automate by truncating at a safe length.
        if (mb_strlen($sanitized, 'UTF-8') > self::MAX_PERSONAL_CONFIRMATION_LENGTH) {
            Log::warning('[ActivityApplied] Personal confirmation truncated for payload safety', [
                'activity_id' => $this->activity->id,
                'user_id' => $this->user->id,
                'original_length' => mb_strlen($sanitized, 'UTF-8'),
                'max_length' => self::MAX_PERSONAL_CONFIRMATION_LENGTH,
            ]);

            $sanitized = mb_substr($sanitized, 0, self::MAX_PERSONAL_CONFIRMATION_LENGTH, 'UTF-8');
        }

        return $sanitized;
    }

    /**
     * Convert plain URLs in text nodes to anchors without touching existing href attributes.
     */
    private function linkifyTextUrls(string $html): string
    {
        try {
            libxml_use_internal_errors(true);

            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

            $xpath = new DOMXPath($dom);
            $textNodes = $xpath->query('//text()');

            if ($textNodes === false) {
                return $html;
            }

            foreach ($textNodes as $textNode) {
                $text = $textNode->nodeValue;

                if (!is_string($text) || !preg_match('/https?:\/\//i', $text)) {
                    continue;
                }

                $parts = preg_split('/(https?:\/\/[^\s<]+)/i', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
                if (!is_array($parts) || count($parts) <= 1) {
                    continue;
                }

                $fragment = $dom->createDocumentFragment();

                foreach ($parts as $part) {
                    if ($part === '') {
                        continue;
                    }

                    if (preg_match('/^https?:\/\//i', $part)) {
                        $url = rtrim($part, '.,;:!?');
                        $link = $dom->createElement('a', $url);
                        $link->setAttribute('href', $url);
                        $fragment->appendChild($link);

                        $trailing = substr($part, strlen($url));
                        if ($trailing !== '') {
                            $fragment->appendChild($dom->createTextNode($trailing));
                        }
                    } else {
                        $fragment->appendChild($dom->createTextNode($part));
                    }
                }

                $textNode->parentNode?->replaceChild($fragment, $textNode);
            }

            $result = $dom->saveHTML();

            return is_string($result) ? $result : $html;
        } catch (Throwable) {
            return $html;
        } finally {
            libxml_clear_errors();
        }
    }

    /**
     * Convert HTTPS URLs into copy-safe text to avoid aggressive link filtering/quarantine.
     */
    private function obfuscateHttpsUrls(string $html): string
    {
        $replaceUrl = function (string $url): string {
            return str_replace('https://', 'https://', $url);
        };

        // Replace anchor tags that point to any HTTPS URL.
        $html = preg_replace_callback(
            '/<a\s+[^>]*href=("|\')(https:\/\/[^"\']+)\1[^>]*>.*?<\/a>/i',
            function (array $matches) use ($replaceUrl) {
                $url = $matches[2];
                $safe = e($replaceUrl($url));

                return '<p><strong>Link:</strong><br>' . $safe . '</p>';
            },
            $html
        ) ?? $html;

        // Replace bare HTTPS URLs.
        $html = preg_replace_callback(
            '/https:\/\/[^\s<]+/i',
            function (array $matches) use ($replaceUrl) {
                return e($replaceUrl($matches[0]));
            },
            $html
        ) ?? $html;

        return $html;
    }

    /**
     * Remove generic greetings like 'Beste Collega' from content to avoid duplicate salutations.
     */
    private function stripGenericGreeting(string $html): string
    {
        // Remove 'Beste Collega' or similar generic greetings at the start
        $html = preg_replace('/<p[^>]*>\s*Beste\s+(?:Collega|Leden)[^<]*<\/p>/i', '', $html) ?? $html;
        
        return trim($html);
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

    /**
     * Replace local storage image URLs with data URI in the given HTML so images are embedded in mails.
     */
    private function inlineLocalImages(string $html): string
    {
        return preg_replace_callback('/<img\s+[^>]*src=("|\')(.*?)\1[^>]*>/i', function (array $matches) {
            $src = trim($matches[2]);

            // Only inline images that live under /storage/ (public disk)
            $storagePrefix = parse_url(Storage::disk('public')->url(''), PHP_URL_PATH) ?: '/storage/';

            if (str_contains($src, '/storage/')) {
                // Derive relative path under storage/app/public
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
}
