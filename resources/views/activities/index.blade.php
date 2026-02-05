<x-page-wrapper page="Activiteiten">
    <x-zijpalm-div name="activity-index-banner" color='transparent' title="Activiteiten" :editable="false"/>
    @auth()
        @if(auth()->user()->is_admin)
            <x-zijpalm-button label="Activiteit Aanmaken" :href="route('activity.create')" center="horizontal"/>
        @endif
    @endauth

    {{-- Container, only show if activities of any kind have been properly given --}}
    @if($activities || $recurringActitivies || $archivedActivities)
        <div id="Activities" class="flex flex-col w-full" x-data="{tabs: ['activities', 'recurring', 'archived'], tab: 'activities', nextTab: null}">
            <x-zijpalm-div title="" color="light" :editable="false" width="w-min-max">
                {{-- Buttons --}}
                <div class="flex flex-wrap justify-center gap-x-4 gap-y-2">
                    <x-zijpalm-button label="Eenmalige activiteiten" type="action" x-on:click="nextTab = tabs[0]; tab = null; setTimeout(() => {tab = nextTab; nextTab = null;}, 475)"/>
                    <x-zijpalm-button label="Wekelijkse activiteiten" type="action" x-on:click="nextTab = tabs[1]; tab = null; setTimeout(() => {tab = nextTab; nextTab = null;}, 475)"/>
                    <x-zijpalm-button label="Oude activiteiten" type="action" x-on:click="nextTab = tabs[2]; tab = null; setTimeout(() => {tab = nextTab; nextTab = null;}, 475)"/>
                </div>
            </x-zijpalm-div>

            <div class="p-5">
                {{-- List of activities --}}
                <x-card-holder :cards="$activities" cardType="activity" :alpine="['x-show' => 'tab === tabs[0]']"/>
                <x-card-holder :cards="$recurringActivities" cardType="activity" :alpine="['x-show' => 'tab === tabs[1]']"/>
                <x-card-holder :cards="$archivedActivities" cardType="activity" :alpine="['x-show' => 'tab === tabs[2]']"/>
            </div>
        </div>
    @endif
</x-page-wrapper>
