<?php
// This file is part of the app logic and has a short comment so it is easier to read.


namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
        $this->email = mb_strtolower(trim($this->email));

        $this->validate();

        $this->ensureIsNotRateLimited();

        $user = User::withTrashed()
            ->whereRaw('LOWER(email) = ?', [$this->email])
            ->first();

        if (! $user) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'E-mailadres klopt niet.',
            ]);
        }

        if (! Hash::check($this->password, (string) $user->password)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'password' => 'Wachtwoord klopt niet.',
            ]);
        }

        if ($user->deleted_at && $user->deleted_at->isPast()) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'U bent geen lid meer',
            ]);
        }

        $remember = (bool) $this->remember;

        // Ensure a remember token exists before logging in with "remember me".
        if ($remember && empty($user->getRememberToken())) {
            $user->setRememberToken(Str::random(60));
            $user->saveQuietly();
        }

        Session::regenerate();
        Auth::login($user, $remember);
        RateLimiter::clear($this->throttleKey());

        // Check for the reset flag either as a query param or as a server-side
        // session key. Livewire actions don't always include query parameters
        // on subsequent calls, so we pull a session flag if present.
        $resetParam = request()->query('reset') || Session::pull('password_reset_success', false);

        if ($this->isModal) {
            // If the login is in a modal, refresh the page
            $this->redirect(url()->previous(), navigate: true);
            return;
        }

        // If the user arrived via a password-reset flow, prefer redirecting to
        // the homepage with the reset flag so the success message is visible.
        if ($resetParam) {
            $this->redirect(route('home', ['reset' => 1]));
            return;
        }

        // Default behaviour: redirect to the intended URL or home
        $this->redirectIntended(default: route('home', absolute: false), navigate: true);
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
