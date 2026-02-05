{{-- Props to accept dynamic title --}}
@props([
    'title' => null,
])

{{-- Convert title to certain format --}}
@php
    if($title){
        $title = config('app.name') . " - " . $title;
    } else {
        $title = config('app.name');
    }
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        {{-- Send title to head to display --}}
        @include('partials.head', ['title' => $title])
    </head>
    <body class="min-h-screen bg-white" x-data="{ loginModalOpen: false }">
        <flux:header container class="bg-linear-to-t from-zinc-300 to-zinc-100 inset-shadow-zinc-50 relative min-h-16">
            <flux:sidebar.toggle class="md:hidden" icon="bars-2" inset="right"/>

            <flux:spacer class="md:hidden"/>

            <a href="{{route('home')}}" class="min-w-max absolute left-1/2 -translate-x-1/2 md:translate-none md:relative md:left-0 md:me-3" wire:navigate>
                <x-app-logo/>
            </a>

            <flux:navbar class="-mb-px max-md:hidden overflow-hidden">
                <flux:navbar.item :href="route('activity.index')" :current="request()->routeIs('activity.*')" wire:navigate>
                    {{__('Activiteiten')}}
                </flux:navbar.item>

                <flux:navbar.item :href="route('information.about')" :current="request()->routeIs('information.about')" wire:navigate>
                    {{__('Over ons')}}
                </flux:navbar.item>

                {{-- TO DO: Add :current, set to any Report page --}}
                <flux:navbar.item :href="route('report.index')" :current="request()->routeIs('report.*')" wire:navigate>
                    {{__('Verslagen')}}
                </flux:navbar.item>

                {{-- TO DO: Add :current, set to 'Goodie' page, SHOP HAS NOT BEEN IMPLEMENTED --}}
{{--                <flux:navbar.item :href="route('product.index')" :current="request()->routeIs('product.*')" wire:navigate>--}}
{{--                    {{__('Shop')}}--}}
{{--                </flux:navbar.item>--}}

                <flux:navbar.item :href="route('information.charity')" :current="request()->routeIs('information.charity')" wire:navigate>
                    {{__('Lief & Leed')}}
                </flux:navbar.item>
            </flux:navbar>

            <flux:spacer/>

            {{-- Admin Panel shortcut --}}
            @auth
                @if(auth()->user()?->is_admin)
                    <div class="hidden md:block">
                        <flux:button square :href="route('admin.index')" variant="primary" icon="wrench-screwdriver" class="mx-1"/>
                    </div>
                @endif
            @endauth

            <!-- Desktop User Menu -->
            <flux:dropdown>
                @auth
                    <flux:button class="cursor-pointer flex space-between">
                        <p class="hidden md:block"> {{auth()->user()->name}} </p>
                        <p class="block md:hidden"> {{auth()->user()->initials()}} </p>
                        <flux:separator vertical class="m-1.5"/>
                        <flux:icon.chevron-down class="size-4"/>
                    </flux:button>
                @else
                <div class="flex flex-col sm:flex-row gap-0.5 sm:gap-1.5">
                    {{-- Opens the login modal --}}
                    <flux:button @click="loginModalOpen = true" class="text-sm px-2! py-0!" size="sm">Inloggen</flux:button>
                    {{-- TO DO: Remove register route, we don't have it --}}
                    @if (Route::has('information.join'))
                        <flux:button variant="primary" href="{{route('information.join')}}" class="text-sm px-2! py-0!" size="sm">Lid Worden</flux:button>
                    @endif
                </div>
                @endauth

                @auth
                <flux:menu>
                    {{-- Display the logged in user's name and email --}}
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5">
                                <div class="grid leading-tight">
                                    <span class="truncate font-semibold">{{auth()->user()->name}}</span>
                                    <span class="truncate text-xs">{{auth()->user()->email}}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    {{-- Settings --}}
                    <flux:menu.radio.group>
                        <flux:menu.item href="{{route('settings.index')}}" icon="cog" wire:navigate>{{ __('Instellingen') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    {{-- Log out button --}}
                    <form method="POST" action="{{route('logout')}}">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-min text-nowrap">
                            {{__('Log uit')}}
                        </flux:menu.item>
                    </form>
                </flux:menu>
                @endauth
            </flux:dropdown>
        </flux:header>

        <!-- Mobile Menu -->
        <flux:sidebar stashable sticky class="lg:hidden border-r border-zinc-400/50 shadow-md w-min">
            <div class="flex">
                <flux:sidebar.toggle class="lg:hidden my-auto w-min pe-2" icon="chevron-left"/>
                <a href="{{route('home')}}" class="w-max" wire:navigate>
                    <x-app-logo/>
                </a>
            </div>

            <flux:separator/>

            <flux:navlist>
                {{-- TO DO: Set :current to any Activity Page --}}
                <flux:navlist.item :href="route('activity.index')" :current="request()->routeIs('activity.*')" icon="calendar-days" iconColor="text-zijpalm-600" iconVariant="solid" iconSize="size-6">
                    <p> Activiteiten </p>
                </flux:navlist.item>

                {{-- TO DO: Add :current, set to any Information page --}}
                <flux:navlist.item :href="route('information.about')" :current="request()->routeIs('information.about')" icon="information-circle" iconColor="text-zijpalm-600" iconVariant="solid" iconSize="size-6">
                    <p> Over ons </p>
                </flux:navlist.item>

                {{-- TO DO: Add :current, set to any Report page --}}
                <flux:navlist.item :href="route('report.index')" :current="request()->routeIs('report.*')" icon="document-text" iconColor="text-zijpalm-600" iconVariant="solid" iconSize="size-6">
                    <p> Verslagen </p>
                </flux:navlist.item>

                {{-- TO DO: Add :current, set to 'Goodie' page, SHOP HAS NOT BEEN IMPLEMENTED--}}
{{--                <flux:navlist.item :href="route('product.index')" :current="request()->routeIs('product.*')" icon="percent-badge" iconColor="text-zijpalm-600" iconVariant="solid" iconSize="size-6">--}}
{{--                    <p> Shop </p>--}}
{{--                </flux:navlist.item>--}}

                {{-- TO DO: Add :current, set to 'Lief & Leed' page --}}
                <flux:navlist.item :href="route('information.charity')" :current="request()->routeIs('information.charity')" icon="heart" iconColor="text-zijpalm-600" iconVariant="solid" iconSize="size-6">
                    <p> Lief & Leed </p>
                </flux:navlist.item>

                {{-- Admin panel --}}
                <flux:navlist.item :href="route('admin.index')" :current="request()->routeIs('admin.*')" icon="wrench-screwdriver" iconColor="text-black" iconVariant="solid" iconSize="size-6">
                    <p> Beheren </p>
                </flux:navlist.item>
            </flux:navlist>

            <flux:spacer />

            <flux:navlist>
                {{-- Items at the bottom of mobile sidebar --}}
                <span class="opacity-50 text-xs"> © Zijpalm 2025 </span>
            </flux:navlist>
        </flux:sidebar>
        {{$slot}}
        @fluxScripts

        {{-- Login modal --}}
        @guest
            <div x-cloak x-init="$nextTick(() => loginModalOpen = false)" class="z-50">
                <!-- Modal Background -->
                <div x-show="loginModalOpen" x-transition class="fixed inset-0 bg-black/50 flex items-center justify-center">
                    <!-- Modal Box -->
                    <div @click.away="loginModalOpen = false" class="bg-white w-9/10 md:w-max max-w-md p-6 rounded-lg shadow-xl relative">
                        <flux:icon.x-mark class="absolute top-4 right-4 cursor-pointer" @click="loginModalOpen = false" />
                        <livewire:auth.login :isModal=true />
                    </div>
                </div>
            </div>
        @endguest
    </body>
</html>
