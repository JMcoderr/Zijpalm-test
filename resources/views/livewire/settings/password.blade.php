<div>
    <x-zijpalm-div :editable=false color="light">
            <x-settings.layout :heading="__('Update uw wachtwoord')" :subheading="__('')">
                <form wire:submit="updatePassword" id="updatePassword" class="mt-6 space-y-6" x-data="{ showPassword: false }" x-effect="$el.querySelectorAll('input[autocomplete*=\'password\']').forEach((input) => input.type = showPassword ? 'text' : 'password')">
                    <flux:input
                        wire:model="current_password"
                        :label="__('Huidig wachtwoord')"
                        type="password"
                        required
                        autocomplete="current-password"
                    />
                    <flux:input
                        wire:model="password"
                        :label="__('Nieuw wachtwoord')"
                        type="password"
                        required
                        autocomplete="new-password"
                    />
                    <flux:input
                        wire:model="password_confirmation"
                        :label="__('Bevestig nieuw wachtwoord')"
                        type="password"
                        required
                        autocomplete="new-password"
                    />

                    <div class="flex items-center justify-end">
                        <label class="inline-flex items-center gap-2 text-sm text-zinc-600 cursor-pointer">
                            <input type="checkbox" x-model="showPassword" class="rounded border-zinc-300">
                            <span>{{ __('Toon wachtwoord') }}</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-center gap-4 w-full">
                        <x-action-message class="me-3" on="password-updated">
                            {{ __('Nieuw wachtwoord opgeslagen') }}
                        </x-action-message>
                    </div>
                </form>
            </x-settings.layout>
    </x-zijpalm-div>
    <x-zijpalm-button type="submit" form="updatePassword" center="horizontal" label="Opslaan" margin="mt-5" />
</div>