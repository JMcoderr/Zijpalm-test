<?php
// This file is part of the app logic and has a short comment so it is easier to read.


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
    use Queueable;

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
        // Store the data for this mail so the view can use it later.
        $this->application = $application;
        $this->activity = $application->activity;
        $this->user = $application->user;

        // Get the dynamic content for the email and cache it for 1 hour
        $this->content = ContentModel::where('name', 'email-activiteit-afgemeld')->first();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        // Build the subject line for this mail.
        return new Envelope(
            subject: 'AUTOMATE SINGLE application_cancelled #Z',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Pass the values to the Blade template that builds the message body.
        // Calculate the total refunded amount
        $this->refundedAmount = $this->application->payments
            ->where('status', \App\PaymentStatus::paid)
            ->sum('price');

        $renderedContent = view('mail.application-cancelled', [
            'application' => $this->application,
            'activity' => $this->activity,
            'user' => $this->user,
            'content' => $this->content,
            'refundedAmount' => $this->refundedAmount,
        ])->render();

        $jsonBody = json_encode([
            'email' => $this->user->email,
            'subject' => $this->content->title . ' ' . $this->activity->title . ' #Z',
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
        // Attach files here if this mail needs them.
        return [];
    }
}
