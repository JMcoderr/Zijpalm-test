<x-page-wrapper page="Admin Verslagen">
    <x-zijpalm-div :editable=false color="light">
        <x-admin.layout :heading="__('Verslagen')" :subheading="__('Bekijk en beheer verslagen per activiteit')">
{{--            <div>--}}
{{--                @if(!empty($reports))--}}
{{--                    @foreach($reports as $report)--}}
{{--                        <x-admin.card :title="$report->content->title" :href="$report->content->file" :buttons="['link' => $report->content->file, 'edit' => route('report.edit', $report->content), 'delete' => route('report.destroy', $report)]"/>--}}
{{--                    @endforeach--}}
{{--                @endif--}}
{{--            </div>--}}
            <x-zijpalm-button type="redirect" label="Nieuw verslag" :href="route('report.create')" center="horizontal" margin="mb-2" />
            <div>
                @if (count($activities) > 0)
                    <x-dropdown title="Verslagen">
                        @foreach ($activities as $report)
                            <x-admin.card :title="$report->content->title" :href="$report->content->file" :variables="[['text' => $report->content->name, 'class' => 'italic text-sm text-gray-400']]" :buttons="['link' => $report->content->file, 'edit' => route('content.edit', $report->content), 'delete' => route('report.destroy', $report)]"/>
                        @endforeach
                    </x-dropdown>
{{--                    <x-dropdown :title="$name" :open="$loop->first" hasNestedDropdown>--}}
{{--                        @foreach ($activities->groupBy(fn($a) => $a->start->format('Y'))->sortKeysDesc() as $year => $yearActivities)--}}
{{--                            <x-dropdown :title="$year" :open="$loop->first">--}}
{{--                                @foreach ($yearActivities as $activity)--}}
{{--                                    <x-admin.card :title="$activity->title" :href="$activity->report ? route('report.show', $activity->report) : null" :buttons="$activity->report ? ['link' => route('report.show', $activity->report), 'edit' => route('report.edit', $activity->report)] : ['add' => route('report.create', $activity)]" />--}}
{{--                                @endforeach--}}
{{--                            </x-dropdown>--}}
{{--                        @endforeach--}}
{{--                    </x-dropdown>--}}
                @endif
            </div>

            <div>
                @if (count($years) > 0)
                    <x-dropdown title="Jaarverslagen">
                        @foreach ($years as $report)
                            <x-admin.card :title="$report->content->title" :href="$report->content->file" :variables="[['text' => $report->content->name, 'class' => 'italic text-sm text-gray-400'], ['text' => $report->year, 'class' => 'bg-blue-600 flex justify-center w-[3rem] h-fit text-white text-xs font-semibold px-2 py-1 rounded-md shadow']]" :buttons="['link' => $report->content->file, 'edit' => route('report.edit', $report->content), 'delete' => route('report.destroy', $report)]"/>
                        @endforeach
                    </x-dropdown>
                @endif
            </div>
        </x-admin.layout>
    </x-zijpalm-div>
</x-page-wrapper>
