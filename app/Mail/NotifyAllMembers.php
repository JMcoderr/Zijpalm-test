<?php
// This file is part of the app logic and has a short comment so it is easier to read.


namespace App\Mail;

use BumpCore\EditorPhp\EditorPhp;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class NotifyAllMembers extends Mailable
{
    use SerializesModels;

    public Collection $emails;
    public array $validatedData;

    /**
     * Create a new message instance.
     */
    public function __construct(Collection $emails, array $validatedData)
    {
        // Store the data for this mail so the view can use it later.
        $this->emails = $emails;
        $this->validatedData = $validatedData;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        // Build the subject line for this mail.
        return new Envelope(
            subject: 'AUTOMATE BATCH notify_all_members #Z',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Pass the values to the Blade template that builds the message body.
        $renderedContent = view('mail.notify-all-members', [
            'description' => EditorPhp::make($this->validatedData['description'])->toHtml(),
        ])->render();

        $jsonBody = json_encode([
            'emails' => $this->emails,
            'subject' => $this->validatedData['subject'] . ' #Z',
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
        // Attach files here if this mail needs them.
        return [];
    }
}
