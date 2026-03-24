<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Reset wachtwoord')" :description="__('Vul uw nieuwe wachtwoord hieronder in')" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="resetPassword" class="flex flex-col gap-6" x-data="{ showPassword: false }" x-effect="$el.querySelectorAll('input[autocomplete*=\'password\']').forEach((input) => input.type = showPassword ? 'text' : 'password')">
        <!-- Email Address -->
        <flux:input
            wire:model="email"
            :label="__('Email')"
            type="email"
            required
            autocomplete="email"
        />

        <!-- Password -->
        <flux:input
            wire:model="password"
            :label="__('Wachtwoord')"
            type="password"
            required
            autocomplete="new-password"
            :placeholder="__('Wachtwoord')"
        />

        <!-- Confirm Password -->
        <flux:input
            wire:model="password_confirmation"
            :label="__('Herhaal wachtwoord')"
            type="password"
            required
            autocomplete="new-password"
            :placeholder="__('Herhaal wachtwoord')"
        />

        <div class="flex items-center justify-end">
            <label class="inline-flex items-center gap-2 text-sm text-zinc-600 cursor-pointer">
                <input type="checkbox" x-model="showPassword" class="rounded border-zinc-300">
                <span>{{ __('Toon wachtwoord') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end">
            <flux:button type="submit" variant="primary" class="w-full">
                {{ __('Reset wachtwoord') }}
            </flux:button>
        </div>
    </form>
</div>
