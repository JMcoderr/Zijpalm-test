@props([
    // Technical props
    'id' => null,
    'name' => null,

    // Title props
    'title' => null,
    'titleSize' => 'text-4xl',
    'titleWeight' => 'font-bold',

    // Input Group props
    'height' => 'h-full',
    'width' => 'w-full',
    'margin' => 'mx-auto',
    'padding' => 'p-0',
    'gap' => 'gap-x-2',
    'grid' => null,
    'flex' => null,

    // Optional
    'drawBoxes' => false,
])

@php
    $titleClasses = [
        $title,
        $titleSize,
        $titleWeight,
    ];

    $inputGroupClasses = [
        $height,
        $width,
        $margin,
        $padding,
        $gap,
    ];

    // Set grid or flex, depending on the set prop
    if($grid){
        array_push($inputGroupClasses, $grid);
    } else if ($flex) {
        array_push($inputGroupClasses, $flex);
    }

    $inputGroupAttributes = $attributes->merge(
        array_filter([
            'class' => implode(' ', $inputGroupClasses),
            // 'id' => $id,
            'name' => $id,
        ],
            // Callback function, fallback option if a value is null/empty
            function($keyValue){
                // Reject if a value isn't null, isn't an empty string, isn't an empty array
                return !is_null($keyValue) && ($keyValue !== '' && $keyValue !== null && $keyValue !== false) || $keyValue === 0;
            }
        )
    );
    
    // Commented out to globally disable, the variable does still exist, but is only used during debugging
    // if($drawBoxes){
    //     array_push($inputGroupClasses, 'border', 'border-black', 'bg-zijpalm-200');
    // }

@endphp

<div id="{{$id}}">
    {{-- Title --}}
    <p @class($titleClasses)> {{$title}} </p>
    <div {{$inputGroupAttributes}}>
        {{$slot}}
    </div>
</div>