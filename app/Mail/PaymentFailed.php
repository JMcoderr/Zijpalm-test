<?php

namespace App\Mail;

use App\Models\Payment;
use App\Models\User;
use App\Models\Content as ContentModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentFailed extends Mailable
{
    use SerializesModels;

    public Payment $payment;
    public User $user;
    public ContentModel $content;

    /**
     * Create a new message instance.
     */
    public function __construct(Payment $payment, User $user)
    {
        $this->payment = $payment;
        $this->user = $user;
        $this->content = getFromCache('email-betaling-mislukt');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'AUTOMATE SINGLE payment_failed',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $renderedContent = view('mail.payment-failed', [
            'payment' => $this->payment,
            'user' => $this->user,
            'content' => $this->content,
        ])->render();

        $jsonBody = json_encode([
            'email' => $this->user->email,
            'subject' => ($this->content->title ?: 'Betaling mislukt') . ': ' . $this->payment->description,
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
