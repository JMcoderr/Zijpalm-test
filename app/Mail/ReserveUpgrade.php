<?php
// This file is part of the app logic and has a short comment so it is easier to read.


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
        // Store the data for this mail so the view can use it later.
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
        // Build the subject line for this mail.
        // Use the content title and application activity title to form the subject
        return new Envelope(
            subject: ($this->content->title ?? 'AUTOMATE SINGLE reserve_upgrade') . ' ' . $this->activity->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Pass the values to the Blade template that builds the message body.
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

        if ($this->content && trim((string) $this->content->text) !== '') {
            $renderedContent = $this->content->mailHtml([
                'user_name' => $this->user->name,
                'total_cost' => number_format($this->totalCost, 2, ',', '.'),
                'payment_link' => $this->paymentLink,
            ]);
        } else {
            $renderedContent = view('mail.reserve-upgrade', [
                'user' => $this->user,
                'content' => $this->content,
                'totalCost' => $this->totalCost,
                'paymentLink' => $this->paymentLink,
            ])->render();
        }

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
        // Attach files here if this mail needs them.
        return [];
    }
}
