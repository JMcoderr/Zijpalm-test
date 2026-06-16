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

                // Build a custom MailMessage
                    return (new MailMessage)
                        ->subject('Wachtwoord vergeten #Z')
                    ->line('Je ontvangt deze e-mail omdat we een wachtwoordresetaanvraag voor je account hebben ontvangen.')
                    ->action('Wachtwoord vernieuwen', $url)
                    ->line('Deze link verloopt over ' . $count . ' minuten.')
                    ->line('Als je geen wachtwoord hebt aangevraagd, hoef je verder niets te doen.');
            }
        );
    }
}
