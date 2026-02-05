<?php

namespace App\Mail;

use App\Models\Activity;
use App\Models\Application;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Content as ContentModel;
use Mollie\Laravel\Facades\Mollie;

class ReserveUpgrade extends Mailable
{
    use SerializesModels;

    public Application $application;
    public Activity $activity;
    public User $user;
    public ContentModel $content;
    public string $paymentLink;
    public float $totalCost;

    /**
     * Create a new message instance.
     */
    public function __construct(Application $application)
    {
        $this->application = $application;
        $this->activity = $application->activity;
        $this->user = $application->user;

        // Get the dynamic content for the email and cache it for 1 hour
        $this->content = getFromCache('email-reserve-upgrade');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        // Use the content title and application activity title to form the subject
        return new Envelope(
            subject: 'AUTOMATE SINGLE reserve_upgrade',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Calculate the total cost for the application
        $this->totalCost = $this->application->calculateTotalCost();

        // Generate a Mollie payment link for the user
        $payment = Payment::generatePaymentLink(
            $this->totalCost,
            "Inschrijving van {$this->application->user->name} voor '{$this->activity->title}'",
            now()->addWeekdays(2)->endOfDay(),
            $this->application->id,
        );

        // Get the payment link from Mollie
        $this->paymentLink = Mollie::api()->paymentLinks->get($payment->mollieId)->_links->paymentLink->href;

        $renderedContent = view('mail.reserve-upgrade', [
            'user' => $this->user,
            'content' => $this->content,
            'totalCost' => $this->totalCost,
            'paymentLink' => $this->paymentLink,
        ])->render();

        $jsonBody = json_encode([
            'email' => $this->user->email,
            'subject' => $this->content->title . ' ' . $this->activity->title,
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
