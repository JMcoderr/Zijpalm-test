{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}

@vite('resources/js/forms/toggle-required.js')
@vite('resources/js/forms/display-uploaded-file-name.js')

<x-page-wrapper page="Bewerk Activiteit">
    <x-zijpalm-div title="Bewerk Activiteit" color="transparent" :editable="false"/>
    <div class="flex flex-col">
        <x-zijpalm-div color="light" :editable=false width="w-full" form>
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

            <div class="mb-3 px-3 py-2 rounded-lg border border-red-200 bg-white/90 text-sm font-medium text-zinc-800 shadow-sm w-max max-w-full mx-auto">
                <span class="text-red-500 font-black text-base align-middle">*</span>
                <span class="align-middle">Verplichte velden</span>
            </div>
            <form id="activity-edit" class="mx-auto p-3 flex flex-col gap-y-2.5" enctype="multipart/form-data" autocomplete="off" method="POST" action="{{ route('activity.update', $activity) }}">
                @csrf
                @method('PUT')

                <x-input-group id="general" title="Algemeen" grid="grid md:grid-cols-2 grid-cols-1">
                    <x-input-group grid padding="p-0" grid="grid lg:grid-cols-2 md:grid-cols-1 grid-cols-2" height="h-min">
                        <x-input-field type="text" label="Titel" id="title" name="title" value="{{ old('title', $activity->title) }}" required/>
                        <x-input-field type="file" label="Afbeelding" id="image-upload" name="image-upload" action="displayUploadedFileName(this)" accept="image/*"/>
                        <x-input-field type="text" label="Locatie" id="location" name="location" value="{{ old('location', $activity->location) }}" required/>
                        <x-input-field type="text" label="Organisator(en)" id="organizer" name="organizer" value="{{ old('organizer', $activity->organizer) }}" required/>
                        <x-input-field type="number" label="Max. Deelnemers" id="maxParticipants" name="maxParticipants" value="{{ old('maxParticipants', $activity->maxParticipants) }}"/>
                        <x-input-field type="number" label="Aantal Intro's (p.p.)" id="maxGuests" name="maxGuests" value="{{ old('maxGuests', $activity->maxGuests) }}"/>
                        <x-input-field type="price" label="Prijs" id="price" name="price" value="{{ old('price', $activity->price) }}" placeholder="Laat leeg voor gratis"/>
                        <x-input-field type="number" label="Aantal gratis organisatoren" id="free_organizer_count" name="free_organizer_count" min="0" value="{{ old('free_organizer_count', $activity->free_organizer_count) }}"/>
                        <x-input-field type="text" label="WhatsApp Groep Link" id="whatsappUrl" name="whatsappUrl" value="{{ old('whatsappUrl', $activity->whatsappUrl) }}"/>
                    </x-input-group>
                    <x-input-group class="items-stretch">
                        @php
                            $descriptionValue = old('description', $activity->description ?? '');
                            $decodedDescription = is_string($descriptionValue)
                                ? json_decode(html_entity_decode($descriptionValue, ENT_QUOTES, 'UTF-8'), true)
                                : null;

                            if (!is_array($decodedDescription) || !array_key_exists('blocks', $decodedDescription)) {
                                $descriptionValue = json_encode([
                                    'time' => now()->timestamp * 1000,
                                    'blocks' => [
                                        [
                                            'type' => 'paragraph',
                                            'data' => ['text' => (string) ($activity->description ?? '')],
                                        ],
                                    ],
                                    'version' => '2.31.0',
                                ], JSON_UNESCAPED_UNICODE);
                            } else {
                                $descriptionValue = json_encode($decodedDescription, JSON_UNESCAPED_UNICODE);
                            }
                        @endphp
                        <x-input-field label="Beschrijving" id="description" name="description" type="editor" :value="$descriptionValue" required/>
                    </x-input-group>
                </x-input-group>

                @php
                    $personalConfirmationValue = old('personalConfirmation', $activity->personal_confirmation ?? '');
                    $decodedPersonalConfirmation = is_string($personalConfirmationValue)
                        ? json_decode(html_entity_decode($personalConfirmationValue, ENT_QUOTES, 'UTF-8'), true)
                        : null;

                    if (!blank($personalConfirmationValue) && (!is_array($decodedPersonalConfirmation) || !array_key_exists('blocks', $decodedPersonalConfirmation))) {
                        $personalConfirmationValue = json_encode([
                            'time' => now()->timestamp * 1000,
                            'blocks' => [
                                [
                                    'type' => 'paragraph',
                                    'data' => ['text' => (string) ($activity->personal_confirmation ?? '')],
                                ],
                            ],
                            'version' => '2.31.0',
                        ], JSON_UNESCAPED_UNICODE);
                    } elseif (is_array($decodedPersonalConfirmation) && array_key_exists('blocks', $decodedPersonalConfirmation)) {
                        $personalConfirmationValue = json_encode($decodedPersonalConfirmation, JSON_UNESCAPED_UNICODE);
                    }
                @endphp

                <x-input-group id="personal-confirmation-group" title="Bevestigingsmail" grid="grid grid-cols-1">
                    <p class="text-sm text-zinc-600">
                        Zet dit alleen aan als je voor deze activiteit een afwijkende bevestigingsmail wilt versturen. Laat je dit uit, dan wordt de standaard bevestigingsmail gebruikt.
                    </p>
                    <x-input-field
                        type="checkbox"
                        label="Persoonlijke bevestigingsmail gebruiken"
                        id="personalConfirmationEnabled"
                        name="personalConfirmationEnabled"
                        :checked="old('personalConfirmationEnabled', $activity->personal_confirmation_enabled)"
                        action="togglePersonalConfirmationField(this)"
                    />
                    <x-input-field
                        label="Tekst persoonlijke bevestigingsmail"
                        id="personalConfirmation"
                        name="personalConfirmation"
                        type="editor"
                        height="h-64"
                        :value="$personalConfirmationValue"
                        :hidden="!old('personalConfirmationEnabled', $activity->personal_confirmation_enabled)"
                    />
                </x-input-group>

                <div class="mt-5">
                @include('activities.partials.manual-finance-fields', [
                    'prefix' => 'edit-manual-finance',
                    'manualFinanceEntries' => $activity->manual_income_entries ?? [],
                ])
                </div>

                @php
                    $weekdayOptions = [
                        ['id' => 1, 'option' => 'maandag'],
                        ['id' => 2, 'option' => 'dinsdag'],
                        ['id' => 3, 'option' => 'woensdag'],
                        ['id' => 4, 'option' => 'donderdag'],
                        ['id' => 5, 'option' => 'vrijdag'],
                        ['id' => 6, 'option' => 'zaterdag'],
                        ['id' => 7, 'option' => 'zondag'],
                    ];
                    $selectedRecurringWeekday = old('recurring_weekday', $activity->start ? $activity->start->dayOfWeekIso : null);
                @endphp

                <flux:separator variant="subtle"/>

                <div class="grid lg:grid-cols-2 grid-cols-1 gap-x-2 relative">
                    <x-input-group id="times" title="Wanneer" height="h-max" grid="grid grid-cols-2">
                        <x-input-field type="date" label="Startdatum" id="start-date" name="start-date" value="{{ old('start-date', $activity->start ? $activity->start->format('Y-m-d') : '') }}" required/>
                        <x-input-field type="date" label="Einddatum" id="end-date" name="end-date" value="{{ old('end-date', $activity->end ? $activity->end->format('Y-m-d') : '') }}" required/>
                        <input type="hidden" name="start-time" value="00:00"/>
                        <input type="hidden" name="end-time" value="23:59"/>
                        <x-input-field type="date" label="Start Aanmeldperiode" id="registrationStart" name="registrationStart" value="{{ old('registrationStart', $activity->registrationStart ? $activity->registrationStart->format('Y-m-d') : '') }}" required/>
                        <x-input-field type="date" label="Eind Aanmeldperiode" id="registrationEnd" name="registrationEnd" value="{{ old('registrationEnd', $activity->registrationEnd ? $activity->registrationEnd->format('Y-m-d') : '') }}" required/>
                        <x-input-field type="checkbox" label="Herhalend" id="recurring" name="recurring" :checked="old('recurring', $activity->type === \App\ActivityType::Weekly)"/>
                        <x-input-field type="select" label="Dag van de week" id="recurring_weekday" name="recurring_weekday" :options="$weekdayOptions" optionValuePair :selected="old('recurring_weekday', $selectedRecurringWeekday)" :hidden="!old('recurring', $activity->type === \App\ActivityType::Weekly)"/>
                        <x-input-field type="checkbox" label="Kosteloos annuleren is niet mogelijk" id="noCancellation" name="noCancellation" :checked="old('noCancellation', is_null($activity->cancellationEnd))" action="toggleCancellationField(this)"/>
                        <x-input-field type="date" label="Kosteloos annuleren kan t/m" id="cancellationEnd" name="cancellationEnd" value="{{ old('cancellationEnd', $activity->cancellationEnd ? $activity->cancellationEnd->format('Y-m-d') : '') }}"/>
                    </x-input-group>

                    <flux:separator class="my-2 lg:hidden" variant="subtle"/>
                    <flux:separator class="absolute h-full left-1/2 right-1/2 my-2 hidden lg:block" variant="subtle" vertical/>

                    <x-input-group id="questions" title="Vragen" height="h-max" padding="p-2">
                        <livewire:question-builder :questions="old('questions', $activity->questions ?? null)"/>
                    </x-input-group>
                </div>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var timesGroup = document.getElementById('times');
                    var recurring = document.getElementById('recurring');
                    var noCancel = document.getElementById('noCancellation');
                    var personalConfirmationEnabled = document.getElementById('personalConfirmationEnabled');
                    var startDate = document.getElementById('start-date');
                    var endDate = document.getElementById('end-date');
                    var registrationStart = document.getElementById('registrationStart');
                    var registrationEnd = document.getElementById('registrationEnd');
                    var recurringWeekday = document.getElementById('recurring_weekday');
                    var recurringWeekdayWrapper = document.getElementById('recurring_weekday-wrapper');
                    var noCancellationWrapper = document.getElementById('noCancellation-wrapper');
                    var cancellationWrapper = document.getElementById('cancellationEnd-wrapper');
                    var cancellationEnd = document.getElementById('cancellationEnd');

                    if(!timesGroup || !recurring) {
                        return;
                    }

                    var fieldsContainer = timesGroup.querySelector('div');
                    if(!fieldsContainer) {
                        return;
                    }

                    var allChildren = Array.from(fieldsContainer.children);
                    var recurringField = allChildren.find(function(el){ return el.id === 'recurring-wrapper'; }) || recurring.closest('[id$="wrapper"]');
                    var toToggle = allChildren.filter(function(el){ return el !== recurringField; });

                    function applyVisibility() {
                        if(recurring.checked) {
                            toToggle.forEach(function(el){ el.classList.add('hidden'); });
                            if(recurringField) recurringField.classList.remove('hidden');
                            recurringWeekdayWrapper?.classList.remove('hidden');
                            if (recurringWeekday) {
                                recurringWeekday.required = true;
                            }
                            if (window.applyRecurringCancellationVisibility) {
                                window.applyRecurringCancellationVisibility(recurring);
                            }
                            if(startDate) {
                                startDate.required = false;
                            }
                            if(endDate) {
                                endDate.required = false;
                            }
                            if(registrationStart) {
                                registrationStart.required = false;
                            }
                            if(registrationEnd) {
                                registrationEnd.required = false;
                            }
                        } else {
                            toToggle.forEach(function(el){ el.classList.remove('hidden'); });
                            if(recurringField) recurringField.classList.remove('hidden');
                            recurringWeekdayWrapper?.classList.add('hidden');
                            if (recurringWeekday) {
                                recurringWeekday.required = false;
                            }
                            if (window.applyRecurringCancellationVisibility) {
                                window.applyRecurringCancellationVisibility(recurring);
                            }
                            if(startDate) {
                                startDate.required = true;
                            }
                            if(endDate) {
                                endDate.required = true;
                            }
                            if(registrationStart) {
                                registrationStart.required = true;
                            }
                            if(registrationEnd) {
                                registrationEnd.required = true;
                            }
                        }

                        if (window.applyRecurringCancellationVisibility) {
                            window.applyRecurringCancellationVisibility(recurring);
                        }
                    }

                    recurring.addEventListener('change', applyVisibility);
                    if(noCancel) {
                        noCancel.addEventListener('change', applyVisibility);
                    }

                    applyVisibility();

                    if (personalConfirmationEnabled) {
                        togglePersonalConfirmationField(personalConfirmationEnabled);
                    }
                });

                function togglePersonalConfirmationField(checkbox) {
                    var wrapper = document.getElementById('personalConfirmation-wrapper');

                    if(!wrapper) {
                        return;
                    }

                    wrapper.classList.toggle('hidden', !checkbox.checked);

                    if (checkbox.checked && window.initializeEditorJsHolders) {
                        requestAnimationFrame(function () {
                            window.initializeEditorJsHolders();
                        });
                    }
                }
                </script>
            </form>
        </x-zijpalm-div>
    </div>
    <x-zijpalm-button form="activity-edit" type="submit" label="Opslaan" variant="obvious" center="horizontal"/>
</x-page-wrapper>

