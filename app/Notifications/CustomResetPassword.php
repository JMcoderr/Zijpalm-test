<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;

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

//        Mail::to(config('mail.bestuur.address'), config('mail.bestuur.name'))
//            ->send(new ResetPasswordMail(
//                $notifiable->getEmailForPasswordReset(),
//                $resetUrl,
//                config('auth.passwords.users.expire')
//            ));

        $renderedContent = view('mail.reset-password', [
            'resetUrl' => $resetUrl,
            'expire' => config('auth.passwords.users.expire'),
            'content' => getFromCache('email-reset-wachtwoord'),
        ])->render();

        $jsonBody = json_encode([
            'email' => $this->recipientEmail,
            'subject' => 'Wachtwoord vergeten',
            'body' => $renderedContent,
        ], JSON_PRETTY_PRINT);

        return (new MailMessage)
//            ->from(config('mail.bestuur.address'), config('mail.bestuur.name'))
            ->subject('AUTOMATE SINGLE reset_password')
            ->text('mail.raw-json', ['jsonBody' => $jsonBody]);
    }
}
