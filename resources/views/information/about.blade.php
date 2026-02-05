<x-page-wrapper page="Over Ons">
        <x-zijpalm-div :id="$banner->id" :name="$banner->name" color='transparent' :editables="['Titel']" :title="$banner->title" width="w-full sm:w-2/3" />

        {{-- Spacer with smoothened transition --}}
        <div class="transition-all duration-1000 md:h-full h-0 hidden md:block"></div>

        {{-- Middle div / Introduction, description --}}
        <x-zijpalm-div :id="$about->id" :name="$about->name" :editables="['Titel', 'Tekst']" :title="$about->title" :text="$about->textHTML" width="w-full sm:w-2/3" />
        <x-zijpalm-div :id="$specialLeave->id" :name="$specialLeave->name" :editables="['Titel', 'Tekst']" :title="$specialLeave->title" :text="$specialLeave->textHTML" color="light" width="w-full sm:w-2/3" />

        {{-- Board members --}}
        <x-zijpalm-div :editable=false title="Bestuur" color="zijpalm" width="w-90dvw sm:w-2/3" class="pb-10" >
            <x-card-holder :cards="$boardMembers" cardType="content" :itemseditable=true :itemseditables="['Afbeelding', 'Titel', 'Beschrijving']" />
            @if (auth()->user()?->is_admin)
                <x-zijpalm-button :href="route('content.create', 'bestuurslid')" label="Bestuurslid toevoegen" center="horizontal" margin="mt-5" />
            @endif
        </x-zijpalm-div>

        {{-- Files --}}
        <x-zijpalm-div :editable=false title="" color="light" flex="flex flex-col gap-y-3.5" width="w-full sm:w-2/3">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-y-3.5">
                <div id="privacy" class="flex flex-col items-center gap-y-2 relative">
                    <x-edit-content :id="$privacy->id" :name="$privacy->name" :editables="['Titel', 'Bestand']" />
                    <flux:icon.lock-closed variant="solid" class="size-10" />
                    {{-- <h3 class="text-lg font-medium">{{$privacy->title}}</h3> --}}
                    <x-zijpalm-button :href="$privacy->file" target="_blank" :label="$privacy->title" />
                </div>
                <div id="rules" class="flex flex-col items-center gap-y-2 relative">
                    <x-edit-content :id="$rules->id" :name="$rules->name" :editables="['Titel', 'Bestand']" />
                    <flux:icon.home variant="solid" class="size-10" />
                    {{-- <h3 class="text-lg font-medium">{{$rules->title}}</h3> --}}
                    <x-zijpalm-button :href="$rules->file" target="_blank" :label="$rules->title" />
                </div>
                <div id="bylaws" class="flex flex-col items-center gap-y-2 relative">
                    <x-edit-content :id="$bylaws->id" :name="$bylaws->name" :editables="['Titel', 'Bestand']" />
                    <flux:icon.document-text variant="solid" class="size-10" />
                    {{-- <h3 class="text-lg font-medium">{{$bylaws->title}}</h3> --}}
                    <x-zijpalm-button :href="$bylaws->file" target="_blank" :label="$bylaws->title" />
                </div>
            </div>
        </x-zijpalm-div>
</x-page-wrapper>
