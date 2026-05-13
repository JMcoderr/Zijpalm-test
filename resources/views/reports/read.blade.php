{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
<x-page-wrapper :page="$report->content->title ?? ($report->year ? 'Jaarverslag' : 'Verslag')">
    <x-zijpalm-div :editable="false" color="transparent" :title="$report->content->title"/>

    @if(auth()->user()?->isAdmin())
        <x-zijpalm-button :href="route('report.edit', $report)" label="Pas verslag aan" center="horizontal"/>
    @endif

    <x-zijpalm-div :editable="false" padding="p-0">
        <div class="rounded-2xl overflow-hidden">

            @if($report->image)
                <div class="flex flex-col justify-center w-full max-h-[90vh]">
                    <img src="{{$report->image}}" alt="Verslag omslag" class="object-cover w-full max-h-[90vh]">
                </div>
            @endif
            
            {{-- Display either the text content or associated file --}}
            <div @class(['pe-6' => $report->year, 'p-2'])>
                @if($report->content?->file)
                    <embed id="pdf-preview" src="{{$report->content->file}}#toolbar=0" type="application/pdf" class="w-full h-auto rounded-xl mx-2 my-1 aspect-[1/1.41]"/>
                @endif
                @if($report->activity)
                    {!!$report->content->textHTML!!}
                @endif
            </div>
        </div>
    </x-zijpalm-div>
</x-page-wrapper>