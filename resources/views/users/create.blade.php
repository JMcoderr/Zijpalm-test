{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
@use('\App\UserType')

<x-page-wrapper page="Nieuw lid">
    <x-zijpalm-div color="transparent" :editable="false" title="Nieuw lid aanmaken"/>

    <x-zijpalm-div color="light" :editable=false>
        <form id="user-create" class="gap-5 flex flex-col" autocomplete="off" method="POST" action="{{ route('user.store') }}">
            @csrf

            <x-input-group id="general-fields" title="Algemeen" grid padding="p-0" grid="grid grid-cols-1 sm:grid-cols-2" height="h-min">
                <x-input-field type="text" label="Voornaam" id="firstName" value="{{ old('firstName') }}" required />
                <x-input-field type="text" label="Achternaam" id="lastName" value="{{ old('lastName') }}" required />
                <x-input-field type="text" label="E-mail" id="email" value="{{ old('email') }}" inputmode="email" required />
                <x-input-field type="phone" label="Telefoonnummer" id="phone" value="{{ old('phone') }}" inputmode="numeric" />
            </x-input-group>

            <x-input-group id="admin" title="Account" padding="p-0" grid="grid grid-cols-1 sm:grid-cols-2" height="h-min">
                <x-input-field type="select" id="type" label="Gebruikerstype" :options="array_filter(UserType::toArray(), fn($type) => $type !== 'system')" optionOnly :selected="old('type', UserType::Gepensioneerde->value)" width="w-full" required />
                <x-input-field type="checkbox" id="is_admin" value="1" label="Beheerder" width="w-max" :checked="old('is_admin')" />
            </x-input-group>

            <p class="text-sm text-zinc-700">
                Na het aanmaken krijgt deze gebruiker automatisch een resetmail om zelf een wachtwoord te kiezen.
            </p>

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
    </x-zijpalm-div>

    <div class="flex justify-center gap-2 mt-4">
        <x-zijpalm-button class="min-w-44 justify-center" type="redirect" :href="route('admin.users')" label="Annuleren" variant="obvious" />
        <x-zijpalm-button class="min-w-44 justify-center" type="submit" form="user-create" label="Aanmaken" variant="obvious" />
    </div>
</x-page-wrapper>