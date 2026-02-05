<x-page-wrapper page="Lid worden">
        <x-zijpalm-div :id="$banner->id" :name="$banner->name" color='transparent' :editables="['Titel']" :title="$banner->title"/>

        {{-- Spacer with smoothened transition --}}
        <div class="transition-all duration-1000 md:h-full h-0 hidden md:block"></div>

        {{-- Middle div / Introduction, description --}}
        <x-zijpalm-div :id="$info->id" :name="$info->name" :editables="['Titel', 'Tekst']" :title="$info->title" :text="$info->textHTML"/>

        {{-- Bottom div / Activity idea box --}}
        <x-zijpalm-div id="become-member" color="light" :editable=false flex="flex flex-col items-center gap-1.5" width="w-1/2">
            <img width="64" height="64" src="https://img.icons8.com/office/80/add-user-group-man-man--v1.png" alt="add-user-group-man-man--v1"/>
            <p>Aanmelden voor ingehuurde medewerkers, stagiaires en gepensioneerden</p>
            <x-zijpalm-button :href="route('information.joinForm')" label="Aanmelden" />
        </x-zijpalm-div>
</x-page-wrapper>