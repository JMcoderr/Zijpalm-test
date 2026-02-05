<div class="flex items-start max-md:flex-col">
    <div class="mr-10 w-full pb-4 md:w-[160px]">
        <flux:navlist>
            <flux:navlist.item :href="route('admin.activities')" wire:navigate>{{ __('Activiteiten') }}</flux:navlist.item>
            <flux:navlist.item :href="route('admin.users')" wire:navigate>{{ __('Gebruikers') }}</flux:navlist.item>
            <flux:navlist.item :href="route('admin.reports')" wire:navigate>{{ __('Verslagen') }}</flux:navlist.item>
            <flux:navlist.item :href="route('admin.content')" wire:navigate>{{ __('Content') }}</flux:navlist.item>
            <flux:navlist.item :href="route('admin.notifyAllMembers')">{{ __('Mail leden') }}</flux:navlist.item>
            <flux:navlist.item :href="route('admin.notifyNewEmployees')">{{ __('Mail nieuwe medewerkers') }}</flux:navlist.item>
        </flux:navlist>
    </div>

    <flux:separator class="hidden md:block my-3 me-5" vertical />
    <flux:separator class="md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6">
        <h1>{{ $heading ?? '' }}</h1>
        <h3>{{ $subheading ?? '' }}</h3>

        <div class="mt-5 w-full flex flex-col gap-y-5">
            {{ $slot }}
        </div>
    </div>
</div>
