<?php

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
use Throwable;

class ActivityApplied extends Mailable
{
    use SerializesModels;

    private const MAX_PERSONAL_CONFIRMATION_LENGTH = 35000;

    public User $user;
    public Activity $activity;
    public ?Application $application;
    public ContentModel $content;
    public ContentModel $reserveContent;
    public $qrcode;
    public bool $reserve;

    /**
     * Create a new message instance.
     */
    public function __construct(Activity $activity, User $user, bool $reserve = false)
    {
        $this->activity = $activity;
        $this->user = $user;
        $this->reserve = $reserve;

        // Get the dynamic content for the email and cache it for 1 hour
        $this->content = getFromCache('email-activiteit-aangemeld');
        $this->reserveContent = getFromCache('email-activiteit-aangemeld-reserve');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'AUTOMATE SINGLE activity_applied',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $mailDebug = true;

        // Generate the QR code for the WhatsApp URL
        if (isset($this->activity->whatsappUrl)) {
            $this->qrcode = (string) QrCode::size(192)->format('png')->generate($this->activity->whatsappUrl);
        }

        if ($mailDebug) {
            Log::debug('[ActivityApplied] Building payload', [
                'activity_id' => $this->activity->id,
                'user_id' => $this->user->id,
                'reserve' => $this->reserve,
                'personal_confirmation_enabled' => (bool) $this->activity->personal_confirmation_enabled,
                'activity_title' => $this->activity->title,
            ]);
        }

        $personalConfirmationHtml = null;
        if ($this->activity->personal_confirmation_enabled) {
            try {
                $personalConfirmationHtml = $this->sanitizeMailHtml((string) $this->activity->personalConfirmationHTML);
            } catch (Throwable $exception) {
                Log::error('[ActivityApplied] Personal confirmation render failed, falling back to default content', [
                    'activity_id' => $this->activity->id,
                    'user_id' => $this->user->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        // Get the application for the user and activity
        $this->application = $this->activity->applications()
            ->where('user_id', $this->user->id)
            ->whereNot('status', ApplicationStatus::Cancelled)
            ->first();

        try {
            $renderedContent = view('mail.activity-applied', [
                'activity' => $this->activity,
                'application' => $this->application,
                'user' => $this->user,
                'content' => $this->content,
                'reserveContent' => $this->reserveContent,
                'qrcode' => $this->qrcode,
                'reserve' => $this->reserve,
                'personalConfirmationHtml' => $personalConfirmationHtml,
            ])->render();

            if ($mailDebug) {
                Log::debug('[ActivityApplied] View rendered', [
                    'activity_id' => $this->activity->id,
                    'user_id' => $this->user->id,
                    'rendered_length' => strlen($renderedContent),
                    'personal_confirmation_used' => !empty($personalConfirmationHtml),
                    'reserve' => $this->reserve,
                ]);
            }
        } catch (Throwable $exception) {
            Log::error('[ActivityApplied] Mail view render failed, using fallback body', [
                'activity_id' => $this->activity->id,
                'user_id' => $this->user->id,
                'error' => $exception->getMessage(),
            ]);

            $introHtml = $this->reserve
                ? ($this->reserveContent->textHTML ?? '')
                : ($personalConfirmationHtml ?: ($this->content->textHTML ?? ''));

            $applicationSummary = $this->application
                ? '<p><strong>Deelnemers:</strong> ' . (int) $this->application->participants . '</p>'
                : '';

            $renderedContent =
                '<p>Beste ' . e($this->user->name) . ',</p>' .
                $introHtml .
                '<p><strong>Activiteit:</strong> ' . e($this->activity->title) . '</p>' .
                '<p><strong>Locatie:</strong> ' . e((string) $this->activity->location) . '</p>' .
                '<p><strong>Start:</strong> ' . e(formatDate($this->activity->start)) . ' om ' . e(formatTime($this->activity->start)) . ' uur</p>' .
                $applicationSummary;
        }

        $jsonBody = json_encode([
            'email' => $this->user->email,
            'subject' => $this->content->title . ' ' . $this->activity->title,
            'body' => $renderedContent,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);

        if ($mailDebug) {
            Log::debug('[ActivityApplied] JSON payload prepared', [
                'activity_id' => $this->activity->id,
                'user_id' => $this->user->id,
                'json_length' => strlen((string) $jsonBody),
                'subject' => $this->content->title . ' ' . $this->activity->title,
                'personal_confirmation_enabled' => (bool) $this->activity->personal_confirmation_enabled,
                'personal_confirmation_in_body' => $personalConfirmationHtml ? str_contains($renderedContent, (string) $personalConfirmationHtml) : false,
            ]);
        }

        if ($jsonBody === false) {
            Log::error('[ActivityApplied] JSON encode failed', [
                'activity_id' => $this->activity->id,
                'user_id' => $this->user->id,
                'error' => json_last_error_msg(),
            ]);

            $jsonBody = json_encode([
                'email' => $this->user->email,
                'subject' => $this->content->title . ' ' . $this->activity->title,
                'body' => '<p>Inschrijving ontvangen voor ' . e($this->activity->title) . '.</p>',
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
     * Keep personal confirmation HTML safe and bounded for downstream automation parsers.
     */
    private function sanitizeMailHtml(string $html): string
    {
        if ($html === '') {
            return '';
        }

        $sanitized = $html;

        // Strip tags that commonly break automation parsing.
        $sanitized = preg_replace('/<\s*(script|style|iframe|object|embed)\b[^>]*>.*?<\s*\/\s*\1\s*>/is', '', $sanitized) ?? $sanitized;

        // Remove non-printable control characters except line breaks and tabs.
        $sanitized = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $sanitized) ?? $sanitized;

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
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
