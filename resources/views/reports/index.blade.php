<x-page-wrapper page="Verslagen" xmlns:flux="http://www.w3.org/1999/html">
    <x-zijpalm-div name="report-index-banner" color='transparent' title="Verslagen" :editable="false"/>

    @auth()
        @if(auth()->user()->is_admin)
            <x-zijpalm-button label="Verslag Aanmaken" :href="route('report.create')" center="horizontal"/>
        @endif
    @endauth

    {{-- Container --}}
    @if($activities || $years)
        <div id="Reports" class="flex flex-col w-full" x-data="{tabs: ['activity', 'year'], tab: 'activity', nextTab: null}">
            <x-zijpalm-div title="" color="light" :editable="false" width="w-min-max">
{{--                 Buttons--}}
                <div class="flex flex-wrap justify-center gap-x-4 gap-y-2">
                    <x-zijpalm-button label="Activiteitsverslagen" type="action" x-on:click="nextTab = tabs[0]; tab = null; setTimeout(() => {tab = nextTab; nextTab = null;}, 80)"/>
                    <x-zijpalm-button label="Jaarverslagen" type="action" x-on:click="nextTab = tabs[1]; tab = null; setTimeout(() => {tab = nextTab; nextTab = null;}, 80)"/>
                </div>
            </x-zijpalm-div>

            <div class="p-5">
{{--                List of reports--}}

                @if(!empty($activities))
                    {{--                <x-card-holder :cards="$activityReports" cardType="report" :alpine="['x-show' => 'tab === tabs[0]']"/>--}}
                    <div id="reports-activities" class="flex flex-wrap w-full justify-center" x-show="tab === 'activity'" x-transition>
                        <div class="p-5 flex flex-wrap gap-4 w-fit">
                            @foreach($activities as $report)
                                <x-zijpalm-div :editable="false" title="" color="light" class="flex flex-col items-center gap-2 relative" width="">
                                    <x-edit-content :id="$report->content->id" :name="$report->content->name" :editables="['Titel', 'Bestand']" />
                                    <flux:icon.document-text variant="solid" class="size-10" />
                                    {{-- <h3 class="text-lg font-medium">{{$bylaws->title}}</h3> --}}
                                    <x-zijpalm-button :href="$report->content->file" target="_blank" :label="$report->content->title" />
                                </x-zijpalm-div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(!empty($years))
                    {{--                <x-card-holder :cards="$yearReports" cardType="report" :alpine="['x-show' => 'tab === tabs[1]']"/>--}}
                    <div id="reports-years" class="flex flex-wrap w-full justify-center" x-show="tab === 'year'" x-transition>
                        <div class="p-5 flex flex-wrap gap-4 w-fit">
                            @foreach($years as $report)
                                <x-zijpalm-div :editable="false" title="" color="light" class="flex flex-col items-center gap-2 relative min-w-[9rem]" width="">
                                    @if(!empty($report->year))
                                        <span class="absolute top-2 left-2 bg-blue-600 text-white text-xs font-semibold px-2 py-1 rounded-md shadow">
                                            {{ $report->year }}
                                        </span>
                                    @endif

                                    <x-edit-content :id="$report->content->id" :name="$report->content->name" :editables="['Titel', 'Bestand']" />
                                    <flux:icon.document-text variant="solid" class="size-10" />
                                    {{-- <h3 class="text-lg font-medium">{{$bylaws->title}}</h3> --}}
                                    <x-zijpalm-button :href="$report->content->file" target="_blank" :label="$report->content->title" />
                                </x-zijpalm-div>
                            @endforeach
                            {{--                <x-card-holder :cards="$reports" cardType="report"/>--}}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
{{--    @if(!empty($reports))--}}
{{--        <div id="reports" class="flex flex-wrap w-full justify-center">--}}
{{--            <div class="p-5 flex flex-wrap gap-4 w-fit">--}}
{{--                @foreach($reports as $report)--}}
{{--                    <x-zijpalm-div :editable="false" title="" color="light" class="flex flex-col items-center gap-2 relative" width="">--}}
{{--                        <x-edit-content :id="$report->content->id" :name="$report->content->name" :editables="['Titel', 'Bestand']" />--}}
{{--                        <flux:icon.document-text variant="solid" class="size-10" />--}}
{{--                        --}}{{-- <h3 class="text-lg font-medium">{{$bylaws->title}}</h3> --}}
{{--                        <x-zijpalm-button :href="$report->content->file" target="_blank" :label="$report->content->title" />--}}
{{--                    </x-zijpalm-div>--}}
{{--                @endforeach--}}
{{--                <x-card-holder :cards="$reports" cardType="report"/>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    @endif--}}
</x-page-wrapper>
