@section('content')
    <x-page-wrapper page="Bewerk Activiteit">
        <x-zijpalm-div title="Bewerk Activiteit" color="transparent" :editable="false"/>
        <div class="flex flex-col">
            <x-zijpalm-div color="light" :editable=false width="w-full" form>
                <form id="activity-edit" class="mx-auto p-3 flex flex-col gap-y-2.5" enctype="multipart/form-data" autocomplete="off" method="POST" action="{{ route('activity.update', $activity) }}">
                    @csrf
                    @method('PUT')

                    {{-- General fields --}}
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
                            <x-input-field label="Beschrijving" id="description" name="description" type="editor" value="{{ old('description', $activity->description) }}" required/>
                        </x-input-group>
                    </x-input-group>

                    <flux:separator variant="subtle"/>


                    {{-- Times --}}
                    <div class="grid lg:grid-cols-2 grid-cols-1 gap-x-2 relative">
                        <x-input-group id="times" title="Tijden" height="h-max" grid="grid grid-cols-2">
                            <x-input-field type="date" label="Startdatum" id="start-date" name="start-date" value="{{ old('start-date', $activity->start ? $activity->start->format('Y-m-d') : '') }}" required/>
                            <x-input-field type="date" label="Einddatum" id="end-date" name="end-date" value="{{ old('end-date', $activity->end ? $activity->end->format('Y-m-d') : '') }}"/>
                            <x-input-field type="date" label="Start Aanmeldperiode" id="registrationStart" name="registrationStart" value="{{ old('registrationStart', $activity->registrationStart ? $activity->registrationStart->format('Y-m-d') : '') }}" required/>
                            <x-input-field type="date" label="Eind Aanmeldperiode" id="registrationEnd" name="registrationEnd" value="{{ old('registrationEnd', $activity->registrationEnd ? $activity->registrationEnd->format('Y-m-d') : '') }}" required/>
                            <x-input-field type="checkbox" label="Herhalend" id="recurring" name="recurring" :checked="old('recurring', $activity->type === \App\ActivityType::Weekly)"/>
                            <x-input-field type="checkbox" label="Kosteloos annuleren is niet mogelijk" id="noCancellation" name="noCancellation" :checked="old('noCancellation', is_null($activity->cancellationEnd))"/>
                            <x-input-field type="date" label="Kosteloos annuleren kan t/m" id="cancellationEnd" name="cancellationEnd" value="{{ old('cancellationEnd', $activity->cancellationEnd ? $activity->cancellationEnd->format('Y-m-d') : '') }}"/>
                        </x-input-group>
                    </div>

                    <flux:separator class="my-2 lg:hidden" variant="subtle"/>
                    <x-zijpalm-button label="Opslaan" center="horizontal" variant="obvious"/>
                </form>
            </x-zijpalm-div>
        </div>
    </x-page-wrapper>

