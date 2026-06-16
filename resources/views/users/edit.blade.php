{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
@use('\App\UserNotifications')
@use('\App\UserType')
@php
    $canUpdateIdentity = auth()->user()->isAdmin();
@endphp
<x-page-wrapper :page="$user->name">

    <x-zijpalm-div color="transparent" :editable="false" title="Gebruikersprofiel aanpassen"/>

    <x-zijpalm-div color="light" :editable=false>
        <x-settings.layout :user="auth()->user() !== $user ? $user : null">
            <form id="user-edit" class="gap-5 flex flex-col" enctype="multipart/form-data" autocomplete="off" method="POST" action="{{route('user.update', $user)}}">
                @csrf
                @method('PUT')

                <x-input-group id="general-fields" title="Algemeen" grid padding="p-0" grid="grid grid-cols-1 sm:grid-cols-2" height="h-min">
                    <x-input-field type="text" label="Voornaam" id="firstName" value="{{$user->firstName}}" required :disabled="!$canUpdateIdentity" />
                    <x-input-field type="text" label="Achternaam" id="lastName" value="{{$user->lastName}}" required :disabled="!$canUpdateIdentity" />
                    <x-input-field type="email" label="E-mail" id="email" value="{{$user->email}}" inputmode="email" required :disabled="!$canUpdateIdentity" />
                    <x-input-field type="email" label="Extra e-mailadres 1" id="emailSecondary" value="{{$user->emailSecondary}}" inputmode="email" :disabled="!$canUpdateIdentity" />
                    <x-input-field type="email" label="Extra e-mailadres 2" id="emailTertiary" value="{{$user->emailTertiary}}" inputmode="email" :disabled="!$canUpdateIdentity" />
                    <x-input-field type="phone" label="Telefoonnummer" id="phone" value="{{$user->phone}}" inputmode="numeric" />
                    @isset($user->employee_number)
                        <x-input-field type="text" label="Medewerkernummer" id="employee_number" value="{{$user->employee_number}}" :disabled="!$user->canUpdatePersonalia()" />
                    @endisset
                </x-input-group>

                <x-input-group title="E-mail updates" class="flex flex-col gap-2" height="h-min">
                    <p>Hier kun je aangeven voor welke gebeurtenissen je mails wilt ontvangen</p>
                    <x-input-field type="checkbox" id="notifications[{{UserNotifications::NEWSLETTER->name}}]" :checked="$user->wantsNotification(UserNotifications::NEWSLETTER)" label="Nieuwsbrief" :disabled="!(auth()->user()->is($user) || auth()->user()->isAdmin())" />
                    <x-input-field type="checkbox" id="notifications[{{UserNotifications::ACTIVITY_REMINDER->name}}]" :checked="$user->wantsNotification(UserNotifications::ACTIVITY_REMINDER)" label="Herinnering activiteit" :disabled="!(auth()->user()->is($user) || auth()->user()->isAdmin())" />
                    <x-input-field type="checkbox" id="notifications[{{UserNotifications::RECURRING_ACTIVITY_REMINDER->name}}]" :checked="$user->wantsNotification(UserNotifications::RECURRING_ACTIVITY_REMINDER)" label="Herinnering terugkerende activiteit" :disabled="!(auth()->user()->is($user) || auth()->user()->isAdmin())" />
                    <x-input-field type="checkbox" id="notifications[{{UserNotifications::ACTIVITY_SIGNUP->name}}]" :checked="$user->wantsNotification(UserNotifications::ACTIVITY_SIGNUP)" label="Inschrijving activiteit" :disabled="!(auth()->user()->is($user) || auth()->user()->isAdmin())" />
                    <x-input-field type="checkbox" id="notifications[{{UserNotifications::REPORTS->name}}]" :checked="$user->wantsNotification(UserNotifications::REPORTS)" label="Verslagen" :disabled="!(auth()->user()->is($user) || auth()->user()->isAdmin())" />
                </x-input-group>

                @if (auth()->user()->isAdmin())
                    <x-input-group id="admin" title="Beheerder functies" padding="p-0" grid="grid grid-cols-2 justify-evenly" height="h-min">
                        <x-input-field type="checkbox" id="is_admin" :checked="$user->isAdmin()" value="1" label="Beheerder" width="w-max" />
                        {{-- Displays an selection --}}
                        @if (!$user->isType(UserType::System))
                            <div class="flex items-end gap-2 w-max">
                                <x-input-field type="select" id="type" label="Gebruikerstype" :options="array_filter(UserType::toArray(), fn($type) => $type !== 'system')" optionOnly :selected="$user->type->value" width="w-max" />
                                <x-zijpalm-button type="submit" form="user-reset-password" label="Reset wachtwoord" />
                            </div>
                        @else
                            <x-input-field type="text" label="Gebruikerstype" id="type" value="{{UserType::System->name}}" :disabled="true" width="w-max" />
                        @endif
                    </x-input-group>
                @endif

                
                @if ($errors->any())
                    <div class="text-red-500">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>                
                @endif
            </form>

            @if (auth()->user()->isAdmin() && !$user->isType(UserType::System))
                <form id="user-reset-password" method="POST" action="{{ route('user.resetPassword', $user) }}">
                    @csrf
                </form>
            @endif
        </x-settings.layout>
    </x-zijpalm-div>
    <x-zijpalm-button type="submit" form="user-edit" center="horizontal" label="Aanpassen" />
</x-page-wrapper>
