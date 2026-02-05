<?php

namespace App\Mail;

use App\Models\Activity;
use App\Models\User;
use BumpCore\EditorPhp\EditorPhp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Content as ContentModel;
use Illuminate\Support\Collection;

class ActivityReminder extends Mailable
{
    use SerializesModels;

    public Activity $activity;
    public ContentModel $content;
    public Collection $emails;
    public array $validatedData;

    /**
     * Create a new message instance.
     */
    public function __construct(Activity $activity, Collection $emails, array $validatedData)
    {
        $this->activity = $activity;
        $this->emails = $emails;
        $this->validatedData = $validatedData;

        $this->content = getFromCache('email-herinnering-activiteit-deelnemers');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'AUTOMATE BATCH activity_reminder',
        );
    }

    /**
     * Get the message content definition. JSON
     */
    public function content(): Content
    {
        $renderedContent = view('mail.activity-reminder', [
            'activity' => $this->activity,
            'user' => null,
            'content' => $this->content,
            'description' => EditorPhp::make($this->validatedData['description'])->toHtml()
        ])->render();

        $jsonBody = json_encode([
            'emails' => $this->emails,
            'subject' => $this->content->title . ' ' . $this->activity->title,
            'body' => $renderedContent,
            'batch_size' => $this->validatedData['batch_size'],
            'delay' => $this->validatedData['delay'],
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
