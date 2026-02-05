<?php

namespace App\Livewire\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.auth')]
class Login extends Component
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public bool $isModal = false;

    public function render()
    {
        // Check if the component is being used as a modal
        // And set the layout accordingly
        if ($this->isModal) {
            return view('livewire.auth.login');
        }

        // If not modal, return the default layout
        return view('livewire.auth.login')
            ->layout('components.layouts.auth');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // Get the user after successful authentication
        $user = Auth::user();

        // Check if the user is deleted
        if ($user->deleted_at && $user->deleted_at->isPast()) {
            // Make sure the user is logged out
            Auth::logout();

            // Send a error message
            throw ValidationException::withMessages([
                'email' => "U bent geen lid meer",
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        if ($this->isModal) {
            // If the login is in a modal, refresh the page
            // It says redirect to previous but it just refreshes the page to update the navbar with the users dropdown
            $this->redirect(url()->previous(), navigate: true);
        } else {
            // Redirect after succesful login
            $this->redirectIntended(default: route('home', absolute: false), navigate: true);
        }
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}
