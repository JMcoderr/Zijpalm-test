<?php

namespace App\Mail;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Content as ContentModel;
use Illuminate\Support\Collection;

class NewActivity extends Mailable
{
    use SerializesModels;

    public Activity $activity;
    public Collection $emails;
    public ContentModel $content;

    /**
     * Create a new message instance.
     */
    public function __construct(Activity $activity, Collection $emails)
    {
        $this->activity = $activity;
        $this->emails = $emails;

        $this->content = getFromCache('email-nieuwe-activiteit');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'AUTOMATE BATCH new_activity',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $renderedContent = view('mail.new-activity', [
            'activity' => $this->activity,
            'user' => null,
            'content' => $this->content,
            'description' => $this->activity->descriptionHTML,
        ])->render();

        $jsonBody = json_encode([
            'emails' => $this->emails,
            'subject' => $this->content->title,
            'body' => $renderedContent,
            'batch_size' => 50,
            'delay' => 30,
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
