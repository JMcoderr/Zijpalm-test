{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
<section class="w-full bg-white">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Profile')" :subheading="__('Only administrators can update your name and email addresses')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" :disabled="!auth()->user()->isAdmin()" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" :disabled="!auth()->user()->isAdmin()" />
                <flux:input wire:model="emailSecondary" label="Extra e-mailadres 1" type="email" autocomplete="email" :disabled="!auth()->user()->isAdmin()" class="mt-4" />
                <flux:input wire:model="emailTertiary" label="Extra e-mailadres 2" type="email" autocomplete="email" :disabled="!auth()->user()->isAdmin()" class="mt-4" />

                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail &&! auth()->user()->hasVerifiedEmail())
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" :disabled="!auth()->user()->isAdmin()">{{ __('Save') }}</flux:button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        <livewire:settings.delete-user-form />
    </x-settings.layout>
</section>
