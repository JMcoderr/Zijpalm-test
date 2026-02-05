<?php

namespace App\Mail;

use BumpCore\EditorPhp\EditorPhp;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class NotifyNewEmployees extends Mailable
{
    use SerializesModels;

    public Collection $emails;
    public array $validatedData;

    /**
     * Create a new message instance.
     */
    public function __construct(Collection $emails, array $validatedData)
    {
        $this->emails = $emails;
        $this->validatedData = $validatedData;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'AUTOMATE BATCH notify_new_employees',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $renderedContent = view('mail.notify-new-employees', [
            'description' => EditorPhp::make($this->validatedData['description'])->toHtml(),
        ])->render();

        $jsonBody = json_encode([
            'emails' => $this->emails,
            'subject' => $this->validatedData['subject'],
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
