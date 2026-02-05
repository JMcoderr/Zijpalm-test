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

class OrderPaid extends Mailable
{
    use SerializesModels;

    public Order $order;
    public User $user;
    public bool $newMember;
    public ContentModel $content;

    /**
     * OrderPaid constructor.
     *
     * Initializes a new instance of the OrderPaid mail class.
     *
     * @param Order $order      The order that has been paid.
     * @param User  $user       The user associated with the order.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
        $this->user = $order->user;

        // Get the dynamic content for the email and cache it for 1 hour
        $this->content = getFromCache('email-bestelling-betaald');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'AUTOMATE SINGLE order_paid',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $renderedContent = view('mail.order-paid', [
            'order' => $this->order,
            'user' => $this->user,
            'content' => $this->content,
        ])->render();

        $jsonBody = json_encode([
            'email' => $this->user->email,
            'subject' => $this->content->title,
            'body' => $renderedContent,
        ], JSON_PRETTY_PRINT);

        return new Content(
            text: 'mail.raw-json',
            with: [
                'jsonBody' => $jsonBody,
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
