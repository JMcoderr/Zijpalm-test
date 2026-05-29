<?php
// This file is part of the app logic and has a short comment so it is easier to read.


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
            subject: $this->validatedData['subject'] ?? 'AUTOMATE BATCH notify_new_employees',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $content = getFromCache('email-informeer-nieuwe-medewerkers');
        if ($content?->text) {
            $renderedContent = $content->mailHtml([
                'description' => EditorPhp::make($this->validatedData['description'])->toHtml(),
                'recipient_count' => is_countable($this->emails) ? count($this->emails) : (int) $this->emails->count(),
            ]);
        } else {
            // Pass the values to the Blade template that builds the message body.
            $renderedContent = view('mail.notify-new-employees', [
                'description' => EditorPhp::make($this->validatedData['description'])->toHtml(),
            ])->render();
        }

        $recipientCount = is_countable($this->emails) ? count($this->emails) : (int) $this->emails->count();
        $batchSize = (int) $this->validatedData['batch_size'];
        $delay = (int) $this->validatedData['delay'];
        $batches = $batchSize > 0 ? (int) ceil($recipientCount / $batchSize) : 0;
        $estimatedSeconds = $batches * $delay;
        $estimatedHuman = gmdate('H\\:i\\:s', $estimatedSeconds);

        $jsonBody = json_encode([
            'emails' => $this->emails,
            'recipient_count' => $recipientCount,
            'subject' => $this->validatedData['subject'],
            'body' => $renderedContent,
            'batch_size' => $batchSize,
            'delay' => $delay,
            'estimated_duration_seconds' => $estimatedSeconds,
            'estimated_duration_human' => $estimatedHuman,
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
