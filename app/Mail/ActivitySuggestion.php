<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ActivitySuggestion extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $email,
        public string $activityName,
        public string $description,
        public array $uploadedAttachments = [],
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nieuwe activiteitssuggestie: ' . $this->activityName . ' #z',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.activity-suggestion',
            with: [
                'attachments' => $this->uploadedAttachments,
            ],
        );
    }

    public function attachments(): array
    {
        $attachmentObjects = [];

        foreach ($this->uploadedAttachments as $attachment) {
            $attachmentObjects[] = Attachment::fromPath(
                storage_path('app/' . $attachment['path'])
            )->as($attachment['name']);
        }

        return $attachmentObjects;
    }
}
