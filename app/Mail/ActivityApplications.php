<?php

namespace App\Mail;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Content as ContentModel;

class ActivityApplications extends Mailable
{
    use SerializesModels;

    public User $user;
    public Activity $activity;
    public ContentModel $content;
    public array $excelFile;

    /**
     * Email which is send to the board when an activities end of cancellation period is reached
     * This email contains all applications for the activity in an excel sheet
     *
     * @param User $user The user who is receiving the email
     * @param Activity $activity The activity for which the applications are being sent
     * @return void
     */
    public function __construct(User $user, Activity $activity, array $excelFile)
    {
        $this->user = $user;
        $this->activity = $activity;
        $this->excelFile = $excelFile;

        $this->content = getFromCache('email-bestuur-activiteit-aanmeldingen');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->content->title . ' ' . $this->activity->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.activity-applications',
            with: [
                'user' => $this->user,
                'activity' => $this->activity,
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
        return [
            Attachment::fromPath($this->excelFile['filePath'])
                ->as($this->excelFile['fileName'])
                ->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ];
    }
}
