<div class="flex flex-col gap-6">
    <x-auth-header
        :title="__('Confirm password')"
        :description="__('This is a secure area of the application. Please confirm your password before continuing.')"
    />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="confirmPassword" class="flex flex-col gap-6" x-data="{ showPassword: false }" x-effect="$el.querySelectorAll('input[autocomplete*=\'password\']').forEach((input) => input.type = showPassword ? 'text' : 'password')">
        <!-- Password -->
        <flux:input
            wire:model="password"
            :label="__('Password')"
            type="password"
            required
            autocomplete="new-password"
            :placeholder="__('Password')"
        />

        <div class="flex items-center justify-end">
            <label class="inline-flex items-center gap-2 text-sm text-zinc-600 cursor-pointer">
                <input type="checkbox" x-model="showPassword" class="rounded border-zinc-300">
                <span>{{ __('Show password') }}</span>
            </label>
        </div>

        <flux:button variant="primary" type="submit" class="w-full">{{ __('Confirm') }}</flux:button>
    </form>
</div>
