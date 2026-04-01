<?php

namespace App\Mail;

use App\Models\Activity;
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

    /**
     * Create a new message instance.
     */
    public function __construct(Collection $emails, Collection $activities)
    {
        $this->emails = $emails;
        $this->activities = $activities;
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
        $renderedContent = view('mail.upcoming-activities-digest', [
            'activities' => $this->activities,
        ])->render();

        $jsonBody = json_encode([
            'emails' => $this->emails,
            'subject' => 'Komende activiteiten van Zijpalm',
            'body' => $renderedContent,
            'batch_size' => config('mail.power_automate.batch_size.default', 50),
            'delay' => config('mail.power_automate.delay.default', 30),
        ], JSON_PRETTY_PRINT);

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
}
