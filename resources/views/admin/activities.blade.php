<x-page-wrapper page="Admin Activiteiten">

    <x-zijpalm-div :editable=false color="light">
        <x-admin.layout :heading="__('Activiteiten')" :subheading="__('Exporteer aanmeldingen van activiteiten')">
            @foreach ($activityGroupsWithDate as $name => $activities)
                @if ($activities->count() > 0)
                    <x-dropdown :title="$name" :open="$loop->first" hasNestedDropdown>
                        @foreach ($activities->groupBy(fn($a) => $a->start->format('Y'))->sortKeysDesc() as $year => $yearActivities)
                            <x-dropdown :title="$year" :open="$loop->first">
                                @foreach ($yearActivities as $activity)
                                    @if($activity->end?->isPast() || $activity->type === App\ActivityType::Weekly || $activity->type === App\ActivityType::Archived || $activity->type === App\ActivityType::Cancelled)
                                        <x-admin.card :title="$activity->title" :href="route('activity.show', $activity)" :buttons="['link' => route('activity.show', $activity), 'download' => route('admin.activities.download', $activity), 'delete' => route('activity.permanentDelete', $activity)]" />
                                    @else
                                        <x-admin.card :title="$activity->title" :href="route('activity.show', $activity)" :buttons="['link' => route('activity.show', $activity), 'download' => route('admin.activities.download', $activity)]" />
                                    @endif
                                @endforeach
                            </x-dropdown>
                        @endforeach
                    </x-dropdown>
                @endif
            @endforeach

            @foreach ($activityGroupsWithoutDate as $name => $activities)
                @if ($activities->count() > 0)
                    <x-dropdown :title="$name">
                        @if($activity->end?->isPast() || $activity->type === App\ActivityType::Weekly || $activity->type === App\ActivityType::Archived || $activity->type === App\ActivityType::Cancelled)
                            <x-admin.card :title="$activity->title" :href="route('activity.show', $activity)" :buttons="['link' => route('activity.show', $activity), 'download' => route('admin.activities.download', $activity), 'delete' => route('activity.permanentDelete', $activity)]" />
                        @else
                            <x-admin.card :title="$activity->title" :href="route('activity.show', $activity)" :buttons="['link' => route('activity.show', $activity), 'download' => route('admin.activities.download', $activity)]" />
                        @endif
                    </x-dropdown>
                @endif
            @endforeach
        </x-admin.layout>
    </x-zijpalm-div>
</x-page-wrapper>
