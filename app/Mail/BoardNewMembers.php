<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Content as ContentModel;

class BoardNewMembers extends Mailable
{
    use SerializesModels;

    public User $user;
    public Collection $members;
    public bool $multiple;
    public ContentModel $content;

    /**
     * Create a new BoardNewMembers instance.
     *
     * @param User $user The user who is receiving the email.
     * @param User[] $members An array of User objects representing new Zijpalm members.
     */
    public function __construct(User $user, Collection $members)
    {
        $this->user = $user;
        $this->members = $members;

        // Determine if there are multiple new members
        $this->multiple = count($members) > 1;

        // Load the content for the email from the cache
        $this->content = getFromCache('email-bestuur-nieuwe-leden');
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
            view: 'mail.board-new-members',
            with: [
                'user' => $this->user,
                'members' => $this->members,
                'multiple' => $this->multiple,
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
