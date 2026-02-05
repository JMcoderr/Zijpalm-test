<div class="flex flex-col gap-y-4 px-2">
    {{-- Button to add a new guest --}}
    <x-zijpalm-button id="new-guest-button" label="Introducee Toevoegen" type="action" wireclick="addGuest" center="horizontal"/>

    {{-- If guests are added --}}
    @if(!empty($guests))
        @foreach($guests as $guest)
        <x-zijpalm-div id="guests[{{$guest['id']}}]" :editable="false" color="light" width="w-full" wire:key="guests[{{$guest['id']}}]">
            <div class="flex w-full justify-evenly items-center">
                {{-- Remove Button --}}
                <div class="p-2">
                    <x-zijpalm-button type="action" variant="remove" wireclick="removeGuest({{$guest['id']}})"/>
                </div>

                {{-- Text fields --}}
                <div class="w-full grid grid-cols-1 sm:grid-cols-2">
                    <x-input-field :attributes="$this->inputAttributes($guest['id'],'text','voornaam', 'firstName')"/>
                    <x-input-field :attributes="$this->inputAttributes($guest['id'],'text','achternaam', 'lastName')"/>
                    <x-input-field :attributes="$this->inputAttributes($guest['id'],'text','telefoonnummer', 'phone')"/>
                    <x-input-field :attributes="$this->inputAttributes($guest['id'],'text','email', 'email')"/>
                </div>

                {{-- 18+ Checkbox --}}
                <div class="pt-3">
                    <x-input-field :attributes="$this->inputAttributes($guest['id'],'checkbox','18+', 'adult')"/>
                </div>
            </div>
        </x-zijpalm-div>
        @endforeach
    @endif
</div>
