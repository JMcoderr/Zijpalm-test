<?php
// This file is part of the app logic and has a short comment so it is easier to read.


namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class CustomResetPassword extends ResetPassword
{
    public string $recipientEmail;

    public function __construct(string $token, string $recipientEmail)
    {
        parent::__construct($token);

        $this->recipientEmail = $recipientEmail;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $resetUrl = url(route('password.reset', [
            'token' => $this->token,
            'email' => $this->recipientEmail,
        ], false));

        return (new MailMessage)
            ->subject('Wachtwoord vergeten')
            ->view('mail.reset-password', [
                'resetUrl' => $resetUrl,
                'expire' => config('auth.passwords.users.expire'),
                'content' => getFromCache('email-reset-wachtwoord'),
            ]);
    }
}
