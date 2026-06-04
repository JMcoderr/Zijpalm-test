<?php
// This file is part of the app logic and has a short comment so it is easier to read.


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
use Illuminate\Support\Str;

class NewActivity extends Mailable
{
    public Activity $activity;
    public Collection $emails;
    public ContentModel $content;
    public int $batchSize;
    public int $delay;

    /**
     * Create a new message instance.
     */
    public function __construct(Activity $activity, Collection $emails, array $options = [])
    {
        // Store the data for this mail so the view can use it later.
        $this->activity = $activity;
        $this->emails = $emails;
        $this->content = ContentModel::where('name', 'email-nieuwe-activiteit')->first();
        if (!isset($options['batch_size']) || !isset($options['delay'])) {
            throw new \InvalidArgumentException('batch_size and delay must be provided in options');
        }
        $this->batchSize = (int) $options['batch_size'];
        $this->delay = (int) $options['delay'];
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        // Build the subject line for this mail.
        return new Envelope(
            subject: 'AUTOMATE BATCH new_activity',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Pass the values to the Blade template that builds the message body.
        $introHtml = $this->stripGreeting((string) ($this->content->textHTML ?? ''));

        $renderedContent = view('mail.new-activity', [
            'activity' => $this->activity,
            'user' => null,
            'content' => $this->content,
            'introHtml' => $introHtml,
            'description' => $this->activity->descriptionHTML,
        ])->render();

        $jsonBody = json_encode([
            'emails' => $this->emails->values()->all(),
            'subject' => $this->withZSuffix((string) $this->content->title),
            'body' => $renderedContent,
            'batch_size' => $this->batchSize,
            'delay' => $this->delay,
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
