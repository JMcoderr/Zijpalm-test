{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Log in op je account')" :description="__('Vul uw email en wachtwoord in om in te loggen')" />

    <!-- Session Status -->
    @php
        $resetFallback = request()->query('reset') ? __('Uw wachtwoord is succesvol gereset. U kunt nu inloggen.') : null;
    @endphp
    <x-auth-session-status class="text-center" :status="session('status') ?? $resetFallback" />
    <form wire:submit="login" class="flex flex-col gap-6">
        <!-- Email Address -->
        <flux:input
            wire:model="email"
            :label="__('Email adres')"
            type="email"
            required
            autofocus
            autocomplete="email"
            placeholder="email@almere.nl"
        />

        <!-- Password -->
        <div class="relative">
            <flux:input
                wire:model="password"
                :label="__('Wachtwoord')"
                type="password"
                viewable
                required
                autocomplete="current-password"
                :placeholder="__('Wachtwoord')"
            />

            {{-- @if (Route::has('password.request'))
                <flux:link class="absolute right-0 top-0 text-sm" :href="route('password.request')" wire:navigate>
                    {{ __('Wachtwoord vergeten?') }}
                </flux:link>
            @endif --}}
        </div>

        <div class="flex justify-between">
            <!-- Remember Me -->
            <flux:checkbox wire:model="remember" :label="__('Onthoud mij')" />
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
