<?php

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
        $this->user = $user;
        $this->order = $order;

        // Get the dynamic content for the email and cache it for 1 hour
        $this->content = getFromCache('email-bestuur-nieuwe-bestelling');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->content->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
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
        return [];
    }
}
