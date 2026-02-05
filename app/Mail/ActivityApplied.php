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
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ActivityApplied extends Mailable
{
    use SerializesModels;

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
        // Generate the QR code for the WhatsApp URL
        if (isset($activity->whatsappUrl)) {
            $this->qrcode = (string) QrCode::size(192)->format('png')->generate($this->activity->whatsappUrl);
        }

        // Get the application for the user and activity
        $this->application = $this->activity->applications()
            ->where('user_id', $this->user->id)
            ->whereNot('status', ApplicationStatus::Cancelled)
            ->first();

        $renderedContent = view('mail.activity-applied', [
            'activity' => $this->activity,
            'application' => $this->application,
            'user' => $this->user,
            'content' => $this->content,
            'reserveContent' => $this->reserveContent,
            'qrcode' => $this->qrcode,
            'reserve' => $this->reserve,
        ])->render();

        $jsonBody = json_encode([
            'email' => $this->user->email,
            'subject' => $this->content->title . ' ' . $this->activity->title,
            'body' => $renderedContent,
        ], JSON_PRETTY_PRINT);

        return new Content(
            text: 'mail.raw-json',
            with: [
                'jsonBody' => $jsonBody
            ],
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
}
