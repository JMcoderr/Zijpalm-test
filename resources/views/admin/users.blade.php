<x-page-wrapper page="Admin Gebruikers"
                x-data="{
                    importEmployees: {{$errors->importEmployees->any() ? 'true' : 'false'}},
                    importMembers: {{$errors->importMembers->any() ? 'true' : 'false'}}
                }"
                x-init="$watch('importEmployees', v => document.body.classList.toggle('overflow-hidden', v));
                        $watch('importMembers', v => document.body.classList.toggle('overflow-hidden', v));"
>
    <x-zijpalm-div :editable=false color="light">

        <x-admin.layout :heading="__('Leden')" :subheading="__('Bekijk en beheer leden')">
            <div class="grid grid-cols-[min-content_min-content] text-left self-center">
                <div class="font-bold border-b-2 border-e-2 border-[rgba(0,0,0,0.55)] px-4 py-2">{{ __('Lid type') }}</div>
                <div class="font-bold border-b-2 border-[rgba(0,0,0,0.55)] px-4 py-2">{{ __('Aantal') }}</div>
                @foreach ($userGroups as $name => $members)
                    <div class="border-e-2 border-[rgba(0,0,0,0.55)] px-4 py-2">{{ $name }}</div>
                    <div class="px-4 py-2">{{ count($members) }}</div>
                    @if (!$loop->last)
                        <div class="border-b border-[rgba(0,0,0,0.55)] col-span-2 mx-2"></div>
                    @endif
                @endforeach
                <div class="font-bold border-t-2 border-e-2 border-[rgba(0,0,0,0.55)] px-4 py-2">{{ __('Totaal') }}</div>
                <div class="font-bold border-t-2 border-[rgba(0,0,0,0.55)] px-4 py-2">
                    {{ array_sum(array_map('count', $userGroups)) }}
                </div>
                <div class="font-bold border-e-2 border-[rgba(0,0,0,0.55)] px-4 py-2">{{ __('Beheerders') }}</div>
                <div class="font-bold px-4 py-2">{{$admins}}</div>
            </div>

            {{-- Creates a dropdown with all the members for each user group --}}
            {{-- Also add deleted users here --}}
            <div class="flex flex-row justify-end">
                <x-zijpalm-modal text="Importeer medewerkers" livewire include="import-members" modal="importEmployees" :variables="['id' => 'import-employees-form', 'endpoint' => route('admin.importEmployees'), 'errors' => $errors->importEmployees->all()]" />
                <x-zijpalm-button type="action" variant="default" label="Importeer medewerkers" x-on:click="importEmployees = true" />

                <x-zijpalm-modal text="Importeer overig" livewire include="import-members" modal="importMembers" :variables="['id' => 'import-members-form', 'endpoint' => route('admin.importMembers'), 'errors' => $errors->importMembers->all()]" />
                <x-zijpalm-button class="ml-2" type="action" variant="default" label="Importeer overig" x-on:click="importMembers = true" />
                {{--                                <x-zijpalm-button href="#" label="Importeer medewerkers" />--}}
            </div>
            @foreach (array_merge($userGroups, ['Oud leden (Meest recent verwijderde eerst)' => $deletedUsers]) as $name => $members)
                @if (count($members) > 0)
                    <x-dropdown :title="$name" :open="$loop->first" class="flex flex-col gap-2">
                        @if($name == 'Medewerkers')
                            @foreach ($members as $member)
                                {{-- If the member is an admin, show a star next to their name --}}
                                <x-admin.card :title="$member->name" :email="$member->email" :href="route('user.edit', $member)" :buttons="['edit' => route('user.edit', $member)]" :icons="$member->is_admin ? ['star'] : []" />
                            @endforeach
                        @elseif($name == 'Oud leden (Meest recent verwijderde eerst)')
                            @foreach ($members as $member)
                                {{-- If the member is an admin, show a star next to their name --}}
                                <x-admin.card :title="$member->name" :email="$member->email" :href="route('user.edit', $member)" :buttons="['edit' => route('user.edit', $member), 'reinstate' => route('admin.reinstateUser', $member)]" :icons="$member->is_admin ? ['star'] : []" />
                            @endforeach
                        @else
                            @foreach ($members as $member)
                                {{-- If the member is an admin, show a star next to their name --}}
                                <x-admin.card :title="$member->name" :email="$member->email" :href="route('user.edit', $member)" :buttons="['edit' => route('user.edit', $member), 'delete' => route('admin.removeUser', $member)]" :icons="$member->is_admin ? ['star'] : []" />
                            @endforeach
                        @endif
                    </x-dropdown>
                @endif
            @endforeach
        </x-admin.layout>
    </x-zijpalm-div>
</x-page-wrapper>
