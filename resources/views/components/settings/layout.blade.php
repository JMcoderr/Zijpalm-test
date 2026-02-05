@props([
    'user' => null,
    'heading' => null,
    'subheading' => null,
])

<div class="flex items-start max-md:flex-col">
    <div class="mr-10 w-full pb-4 md:w-max">
        <flux:navlist>
            @if($user)
                <flux:navlist.item :href="route('user.edit', $user)" wire:navigate :current="request()->routeIs('user.edit')">{{ __('Profiel') }}</flux:navlist.item>
                <flux:navlist.item :href="route('user.cancel', $user)" wire:navigate :current="request()->routeIs('user.cancel')">{{ __('Afmelden') }}</flux:navlist.item>
            @else
                <flux:navlist.item :href="route('settings.profile')" wire:navigate :current="request()->routeIs('settings.profile')">{{ __('Profiel') }}</flux:navlist.item>
                <flux:navlist.item :href="route('settings.password')" wire:navigate :current="request()->routeIs('settings.password')">{{ __('Wachtwoord') }}</flux:navlist.item>
                <flux:navlist.item :href="route('settings.cancel')" wire:navigate :current="request()->routeIs('settings.cancel')">{{ __('Afmelden') }}</flux:navlist.item>
            @endif
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
