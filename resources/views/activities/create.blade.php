{{-- Load used JavaScripts --}}
{{-- Used for changing required fields based on checkbox --}}
@vite('resources/js/forms/toggle-required.js')
{{-- Used for changing default enter key behaviour --}}
{{-- @vite('resources/js/forms/enter-key-behaviour.js') --}}
{{-- Used for updating file input's placeholder to show the uploaded file's name --}}
@vite('resources/js/forms/display-uploaded-file-name.js')

<x-page-wrapper page="Nieuwe Activiteit">
    {{-- Show errors, if any --}}
    @if($errors->any())
        <x-zijpalm-div color="light" title="Foutmelding(en)" :editable="false" width="min-w-min" error id="error-messages" onclick="this.remove()">
            <ul class="text-center">
                @foreach($errors->all() as $error)
                    <li class="">{{$error}}</li>
                @endforeach
            </ul>
        </x-zijpalm-div>
        <script>
            setTimeout(function(){
                const errorDiv = document.getElementById('error-messages');
                if(errorDiv){
                    errorDiv.remove();
                }
            }, 5000);
        </script>
    @endif

    {{-- Div to include Title and Form divs together --}}
    <x-zijpalm-div title="Nieuwe Activiteit" color="transparent" :editable=false/>
    <div class="flex flex-col">
        <x-zijpalm-div color="light" :editable=false width="w-full" form>
            <div class="mb-3 px-3 py-2 rounded-lg border border-red-200 bg-white/90 text-sm font-medium text-zinc-800 shadow-sm w-max max-w-full mx-auto">
                <span class="text-red-500 font-black text-base align-middle">*</span>
                <span class="align-middle">Verplichte velden</span>
            </div>
            <form id="activity-create" class="mx-auto p-3 flex flex-col gap-y-2.5" enctype="multipart/form-data" autocomplete="off" method="POST" action="{{route('activity.store')}}">
                @csrf

                {{-- Input group used to give title and organise children --}}
                <x-input-group id="general" title="Algemeen" grid="grid md:grid-cols-2 grid-cols-1">
                    <x-input-group grid padding="p-0" grid="grid lg:grid-cols-2 md:grid-cols-1 grid-cols-2" height="h-min">
                        <x-input-field type="text" label="Titel" id="title" required/>
                        <x-input-field type="file" label="Afbeelding" id="image-upload" action="displayUploadedFileName(this)" accept="image/*" required/>
                        <x-input-field type="text" label="Locatie" id="location" required/>
                        <x-input-field type="text" label="Organisator(en)" id="organizer" value="{{auth()->user()?->name}}" required/>
                        <x-input-field type="number" label="Max. Deelnemers" id="maxParticipants"/>
                        <x-input-field type="number" label="Aantal Intro's (p.p.)" id="maxGuests"/>
                        {{-- Price is optional: leave empty for free activities --}}
                        <x-input-field type="price" label="Prijs" id="price" placeholder="Laat leeg voor gratis"/>
                        <x-input-field type="number" label="Aantal gratis organisatoren" id="free_organizer_count" name="free_organizer_count" min="0" value="0"/>
                        <x-input-field type="text" label="WhatsApp Groep Link" id="whatsappUrl" information="whatsapp-info"/>
                    </x-input-group>
                    {{-- TO DO: Replace with Text Editor instead of Textarea --}}
                    <x-input-group class="items-stretch">
                        {{-- May god forgive me for adding such awful breakpoints, it was the only way to keep my sanity, but my pride is obliterated --}}
                        <x-input-field label="Beschrijving" id="description" type="editor" class="" required/>
                    </x-input-group>
                </x-input-group>

                <x-input-group id="personal-confirmation-group" title="Bevestigingsmail" grid="grid grid-cols-1">
                    <p class="text-sm text-zinc-600">
                        Zet dit alleen aan als je voor deze activiteit een persoonlijke bevestigingsmail wilt versturen. Laat je dit uit, dan wordt de standaard bevestigingsmail gebruikt.
                    </p>
                    <x-input-field
                        type="checkbox"
                        label="Persoonlijke bevestigingsmail gebruiken"
                        id="personalConfirmationEnabled"
                        :checked="old('personalConfirmationEnabled')"
                        action="togglePersonalConfirmationField(this)"
                    />
                    <x-input-field
                        label="Tekst persoonlijke bevestigingsmail"
                        id="personalConfirmation"
                        type="editor"
                        height="h-64"
                        :value="old('personalConfirmation')"
                        :hidden="!old('personalConfirmationEnabled')"
                    />
                </x-input-group>

                <flux:separator variant="subtle"/>

                <div class="grid lg:grid-cols-2 grid-cols-1 gap-x-2 relative">
                    <x-input-group id="times" title="Wanneer" height="h-max" grid="grid grid-cols-2">
                        <x-input-field type="date" label="Startdatum" id="start-date" required/>
                        <x-input-field type="date" label="Einddatum" id="end-date" required/>
                        {{-- Standaard tijdwaarden zodat de backend correct blijft werken --}}
                        <input type="hidden" name="start-time" value="00:00"/>
                        <input type="hidden" name="end-time" value="23:59"/>
                        <x-input-field type="date" label="Start Aanmeldperiode" id="registrationStart" required/>
                        <x-input-field type="date" label="Eind Aanmeldperiode" id="registrationEnd" required/>
                        <x-input-field type="checkbox" label="Herhalend" id="recurring" action="toggleRecurringOnChecked(this, document.getElementById('times').querySelectorAll('input'))"/>
                        <x-input-field type="checkbox" label="Kosteloos annuleren is niet mogelijk" id="noCancellation" action="toggleCancellationField(this)"/>
                        <x-input-field type="date" label="Kosteloos annuleren kan t/m" id="cancellationEnd"/>
                    </x-input-group>

                    <flux:separator class="my-2 lg:hidden" variant="subtle"/>
                    <flux:separator class="absolute h-full left-1/2 right-1/2 my-2 hidden lg:block" variant="subtle" vertical/>

                    <x-input-group id="questions" title="Vragen" height="h-max" padding="p-2">
                        <livewire:question-builder :questions="old('questions')"/>
                    </x-input-group>
                </div>
            </form>
        </x-zijpalm-div>
    </div>
    <x-zijpalm-button form="activity-create" type="submit" label="Aanmaken" variant="obvious" center="horizontal"/>

    <script>
        function togglePersonalConfirmationField(checkbox) {
            const wrapper = document.getElementById('personalConfirmation-wrapper');

            if (!wrapper) {
                return;
            }

            wrapper.classList.toggle('hidden', !checkbox.checked);

            if (checkbox.checked && window.initializeEditorJsHolders) {
                requestAnimationFrame(() => window.initializeEditorJsHolders());
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const checkbox = document.getElementById('personalConfirmationEnabled');

            if (checkbox) {
                togglePersonalConfirmationField(checkbox);
            }
        });
    </script>

</x-page-wrapper>
