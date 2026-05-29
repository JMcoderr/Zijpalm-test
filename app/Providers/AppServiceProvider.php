<?php
// This file is part of the app logic and has a short comment so it is easier to read.


namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Override the default reset password notification
        ResetPassword::toMailUsing(function ($notifiable, $token) {
                // Create the URL as Laravel normally does
                $url = url(route('password.reset', [
                    'token' => $token,
                    'email' => $notifiable->getEmailForPasswordReset(),
                ], false));

                // Get the expiration time from the config
                // This is the time in minutes that the reset link is valid
                $count = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

                $content = getFromCache('email-reset-wachtwoord');

                // Build a custom MailMessage using the editable content if available
                return (new MailMessage)
                    ->subject($content->title ?? 'Wachtwoord vergeten')
                    ->view('mail.reset-password', [
                        'resetUrl' => $url,
                        'expire' => $count,
                        'content' => $content,
                    ]);
            }
        );
    }
}
