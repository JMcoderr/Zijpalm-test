<?php

namespace App\Livewire\Auth;

use App\Notifications\CustomResetPassword;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.auth')]
class ForgotPassword extends Component
{
    public string $email = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        // Find the user by email (needed to generate a valid password reset token)
        $user = \App\Models\User::withTrashed()->where('email', $this->email)->first();

        if (!$user) {
            session()->flash('status', __('Er is een link verzonden naar uw email')); // Don't reveal if email exists
        } else {

            $token = Password::createToken($user);

            Notification::route('mail', config('mail.bestuur.address'))
                ->notifyNow(new CustomResetPassword($token, $this->email));
//        Password::sendResetLink($this->only('email'));

            session()->flash('status', __('Er is een link verzonden naar uw email'));
        }
    }
}
