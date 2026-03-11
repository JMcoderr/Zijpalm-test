@props([
    // Expect content and type
    'content' => null,
    'type' => null,

    // By default, disable editability
    'editable' => false,
    'editables' => [],

    // Transition prop, in case the parents sends it.
    'transitions' => null,

    // Style, just default 'light' colour for now.
    'color' => 'light',
])

@php
    // Use the activity model
    use App\Models\Activity;

    // Check if an activity is given and that it's of the Activity model
    if($type === 'activity'){
        $content instanceof Activity || throw new Exception("Given activity is not of the Activity model");

        // Assign for clarity
        $activity = $content;

        $id = $activity->id;
        $image = $activity->image;
        $title = $activity->title;
        $description = $activity->location;
    }

    if($type === 'content'){
        $id = $content->id;
        $image = $content->file;
        $title = $content->title;
        $description = $content->text;
    }

    // Use the report model
    use App\Models\Report;

    if($type === 'report'){
        $content instanceof Report || throw new Exception("Given report is not of the Report model");

        // Assign for clarity
        $report = $content;

        // Sort out what image to display first
        $imgSrc = null;

        // If the report has an activity bound
        if(isset($report->content->file) && !$report->year){
            $imgSrc = $report->content->file;
        }
        else{
            $imgSrc = Storage::url('/images/logo.png');
        }

        $id = $report->id;
        $image = $imgSrc;
        $title = $report->content->title ?: $report->activity->title;
        $description = '';
    }

    // Container classes (x-zijpalm-div)
    $containerClasses = [
        'flex',
        'flex-col',
        'sm:flex-1',
        'aspect-5/6',
        'max-w-[24rem]',
        'w-[90dvw]',
        'sm:max-w-6/12',
        'sm:min-w-6/12',
        'md:max-w-[24rem]',
        'md:min-w-[24rem]',
        'duration-400',
    ];

    // Classes for the div wrapping the image
    $imageDivClasses = [
        'flex',
        'flex-col',
        'rounded-t-xl',
        'border-b',
        'border-[rgba(0,0,0,0.15)]',
        'overflow-hidden',
        'justify-center',
        'flex-3/5',
        'bg-white',
    ];

    // Info classes (non-image half)
    $infoClasses = [
        'flex',
        'flex-col',
        'pb-2',
        'px-3',
    ];

    // Make sure activity images display at reasonable proportions
    if($type === 'activity'){
        array_push($infoClasses, 'sm:flex-2/5');
    }

    // Classes for the title
    $titleClasses = [
        'text-xl',
        'mt-1',
        '-mx-1.5',
        'font-bold',
        'whitespace-nowrap',
        'overflow-hidden',
        'text-ellipsis',
    ];

    // Classes for the details
    $detailClasses = [
        'flex',
        'justify-evenly',
        'sm:gap-2',
        'overflow-hidden',
        'overflow-ellipsis',
        'text-nowrap',
        'break-words',
        'text-center',
    ];

    // Classes for text
    $textClasses = [
        'font-extrabold',
    ];

    // Merge container classes and alpine animations into container attributes
    $containerAttributes = $attributes->merge(
        array_filter([
            // Classes
            'class' => implode(' ', $containerClasses),
        ],
            function($value){
                return !is_null($value) && $value !== '';
            }
        )
    )->when($transitions, fn($attributes) => $attributes->merge($transitions));
@endphp

<div {{$containerAttributes}}>
    <x-zijpalm-div :id="$id" :name="$title" :color="$color" :editable="$editable" :editables="$editables" width="w-full" padding="p-0" class="h-full flex flex-col relative overflow-hidden">
        {{-- Shows a white corner so the edit cog is obvious --}}
        @if($editable && auth()->user()?->is_admin)
            <div class="bg-linear-to-t from-zinc-300 to-zinc-100 inset-shadow-50 absolute top-0 right-0 size-10 rounded-bl-2xl"></div>
        @endif

        {{-- Card Image --}}
        <div @class($imageDivClasses)>
            <img src="{{$image}}" loading="lazy" @class([($image === Storage::url('/images/logo.png') ? 'p-5 object-fit' : 'size-full object-cover')])>
        </div>

        {{-- Details, startdate/time & location --}}
        <div @class($infoClasses)>
            <span @class($titleClasses)>{{$title}}</span>

            @if($type === 'activity') <flux:separator class="mb-1"/> @endif

            <div @class($detailClasses)>
                @if($type === 'activity')
                    <div class="flex flex-col w-1/2">
                        <span @class($textClasses)>Wanneer</span>
                        <span class="text-wrap">
                            {{$content->formattedDatesAndTimes->activity->full}}
                        </span>
                    </div>
                    <div class="flex flex-col w-1/2">
                        <span @class($textClasses)>Locatie</span>
                        <span class="text-wrap break-words">{{$description}}</span>
                    </div>
                @endif

                @if($type === 'content')
                    <div class="flex flex-col">
                        <span>{{$description}}</span>
                    </div>
                @endif
            </div>

            @if($type === 'activity')
                <flux:separator class="my-2"/>
                <x-zijpalm-button label="Bekijk Activiteit" href="{{route('activity.show', $content)}}" center="horizontal"/>
            @endif

            @if($type === 'report')
                <flux:separator class="mt-1 mb-2"/>
                <x-zijpalm-button label="Lees Verslag" href="{{route('report.show', $content)}}" center="horizontal"/>
            @endif
        </div>
    </x-zijpalm-div>
</div>
