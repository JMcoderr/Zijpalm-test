<?php

namespace App\Mail;

use App\Models\Activity;
use App\Models\Application;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Content as ContentModel;
use App\Models\Payment;

class ApplicationCancelled extends Mailable
{
    use Queueable, SerializesModels;

    public Application $application;
    public Activity $activity;
    public float $refundedAmount;
    public User $user;
    public ContentModel $content;


    /**
     * Create a new message instance.
     */
    public function __construct(Application $application)
    {
        $this->application = $application;
        $this->activity = $application->activity;
        $this->user = $application->user;

        // Get the dynamic content for the email and cache it for 1 hour
        $this->content = getFromCache('email-activiteit-afgemeld');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'AUTOMATE SINGLE application_cancelled',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Calculate the total refunded amount
        $this->refundedAmount = $this->application->payments->sum('price');

        $renderedContent = view('mail.application-cancelled', [
            'application' => $this->application,
            'activity' => $this->activity,
            'user' => $this->user,
            'content' => $this->content,
            'refundedAmount' => $this->refundedAmount,
        ])->render();

        $jsonBody = json_encode([
            'email' => $this->user->email,
            'subject' => $this->content->title . ' ' . $this->activity->title,
            'body' => $renderedContent,
        ], JSON_PRETTY_PRINT);

        return new Content(
            text: 'mail.raw-json',
            with: [
                'jsonBody' => $jsonBody
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
