<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use App\Models\Content as ContentModel;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Password;

class NewMember extends Mailable
{
    use SerializesModels;

    public User $user;
    public $resetPasswordToken;
    public ContentModel $content;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;

        // Get the dynamic content for the email and cache it for 1 hour
        $this->content = getFromCache('email-nieuw-lid');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'AUTOMATE SINGLE new_member',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Get a password reset token for the user
        $this->resetPasswordToken = Password::createToken($this->user);

        $renderedContent = view('mail.new-member', [
            'user' => $this->user,
            'resetPasswordToken' => $this->resetPasswordToken,
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
