{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Log in op je account')" :description="__('Vul uw email en wachtwoord in om in te loggen')" />

    <!-- Session Status -->
    @php
        $resetFallback = request()->query('reset') ? __('Uw wachtwoord is succesvol gereset. U kunt nu inloggen.') : null;
    @endphp
    <x-auth-session-status class="text-center" :status="session('status') ?? $resetFallback" />
    
    <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-6">
        @csrf
        
        <!-- Email Address -->
        <flux:input
            name="email"
            :label="__('Email adres')"
            type="email"
            required
            autofocus
            autocomplete="email"
            placeholder="email@almere.nl"
            value="{{ old('email') }}"
        />

        <!-- Password -->
        <div class="relative">
            <flux:input
                name="password"
                :label="__('Wachtwoord')"
                type="password"
                viewable
                required
                autocomplete="current-password"
                :placeholder="__('Wachtwoord')"
            />
        </div>

        <div class="flex justify-between items-center">
            <!-- Remember Me -->
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="remember" value="on" class="w-4 h-4">
                <span>{{ __('Onthoud mij') }}</span>
            </label>
            <flux:link class="text-sm" :href="route('password.request')" wire:navigate>
                {{ __('Wachtwoord vergeten?') }}
            </flux:link>
        </div>

        <div class="flex items-center justify-end">
            <flux:button variant="primary" type="submit" class="w-full">{{ __('Log in') }}</flux:button>
        </div>
    </form>

    @if (Route::has('register'))
        <div class="space-x-1 text-center text-sm text-zinc-600">
            {{ __('Bent u nog geen lid?') }}
            <flux:link :href="route('register')" wire:navigate>{{ __('Word lid') }}</flux:link>
        </div>
    @endif
</div>
