 <div class="flex flex-col gap-6">
    <x-auth-header :title="__('Wachtwoord vergeten?')" :description="__('Vul uw email in om een wachtwoord reset link te ontvangen')" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="sendPasswordResetLink" class="flex flex-col gap-6">
        <!-- Email Address -->
        <flux:input
            wire:model="email"
            :label="__('Email adres')"
            type="email"
            required
            autofocus
            placeholder="email@almere.nl"
        />

        <flux:button variant="primary" type="submit" class="w-full">{{ __('Email wachtwoord reset link') }}</flux:button>
    </form>

    <div class="space-x-1 text-center text-sm text-zinc-400">
        {{ __('Of ga terug naar') }}
        <flux:link :href="route('login')" wire:navigate>{{ __('log in') }}</flux:link>
    </div>
</div>
