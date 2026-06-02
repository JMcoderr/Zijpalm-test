<?php
// This file is part of the app logic and has a short comment so it is easier to read.


namespace App\Mail;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Content as ContentModel;

class BoardNewOrder extends Mailable
{
    use SerializesModels;

    public User $user;
    public Order $order;
    public ContentModel $content;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, Order $order)
    {
        // Store the data for this mail so the view can use it later.
        // Store the user and order so the template can use them later.
        $this->user = $user;
        $this->order = $order;

        // Load the mail text from cache so we do not query the database every time.
        $this->content = getFromCache('email-bestuur-nieuwe-bestelling');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        // Build the subject line for this mail.
        // Use the title from the content record as the subject line.
        return new Envelope(
            subject: $this->content->title . ' #Z',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Pass the values to the Blade template that builds the message body.
        // Pass all values to the Blade mail view so it can build the message.
        return new Content(
            view: 'mail.board-new-order',
            with: [
                'order' => $this->order,
                'user' => $this->user,
                'content' => $this->content,
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
        // Attach files here if this mail needs them.
        // This mail does not need files attached.
        return [];
    }
}
