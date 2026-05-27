{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Reset wachtwoord')" :description="__('Vul uw nieuwe wachtwoord hieronder in')" />

    @if ($resetSuccess)
        <x-auth-session-status class="text-center" :status="$resetSuccessMessage" />

        <div class="flex items-center justify-center">
            <flux:button :href="route('home')" variant="primary" class="w-full" wire:navigate>
                {{ __('Ga terug naar de homepagina') }}
            </flux:button>
        </div>
    @else
        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form wire:submit="resetPassword" class="flex flex-col gap-6">
        <!-- Email Address -->
        <flux:input
            value="{{ $email }}"
            :label="__('Email')"
            type="email"
            required
            autocomplete="email"
            readonly
        />

        <!-- Password -->
        <flux:input
            wire:model="password"
            :label="__('Wachtwoord')"
            type="password"
            viewable
            required
            autocomplete="new-password"
            :placeholder="__('Wachtwoord')"
        />

        <!-- Confirm Password -->
        <flux:input
            wire:model="password_confirmation"
            :label="__('Herhaal wachtwoord')"
            type="password"
            viewable
            required
            autocomplete="new-password"
            :placeholder="__('Herhaal wachtwoord')"
        />

        <div class="flex items-center justify-end">
            <flux:button type="submit" variant="primary" class="w-full">
                {{ __('Reset wachtwoord') }}
            </flux:button>
        </div>
        </form>
    @endif
</div>
