<?php
// This file is part of the app logic and has a short comment so it is easier to read.


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
        // Store the data for this mail so the view can use it later.
        $this->payment = $payment;
        $this->user = $user;
        $this->content = getFromCache('email-betaling-mislukt');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        // Build the subject line for this mail.
        return new Envelope(
            subject: ($this->content->title ?? 'Betaling mislukt') . ': ' . $this->payment->description,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Pass the values to the Blade template that builds the message body.
        if ($this->content && trim((string) $this->content->text) !== '') {
            $renderedContent = $this->content->mailHtml([
                'user_name' => $this->user->name,
                'payment_description' => $this->payment->description,
                'amount' => number_format($this->payment->amount, 2, ',', '.'),
            ]);
        } else {
            $renderedContent = view('mail.payment-failed', [
                'payment' => $this->payment,
                'user' => $this->user,
                'content' => $this->content,
            ])->render();
        }

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
        // Attach files here if this mail needs them.
        return [];
    }
}
