{{-- Props --}}
@props([
    // Editing details
    'editables' => ['Geen'],
    'editable' => true,

    // Content details
    'title' => '',
    'text' => '',

    // Meta information
    'id' => null,
    'name' => 'new-div',
    'form' => false,
    'error' => false,
    'success' => false,

    // General customisation
    'color' => 'zijpalm',
    'margin' => null,
    'padding' => 'p-2',
    'width' => null,
    'flex' => null,
    'display' => null,

    // Title customisation
    'titleFontSize' => 'text-3xl',
    'titleFontWeight' => 'font-bold',
    'titleColor' => null,
    'titleTextShadow' => '',

    // Text customisation
    'textSize' => '',
    'textFontWeight' => 'font-medium',
    'textColor' => null,
    'textIsLink' => false,
    'href' => '#',
])

@php
    // Classes for the div itself
    $divClasses = [
        'self-center',
        $margin,
        $width,
        $padding,
        $flex,
        $display,
    ];

    // Classes affecting title
    $titleClasses = [
        $titleColor,
        $titleFontSize,
        $titleFontWeight,
        'font-bold',
    ];

    // Classes affecting paragraph text
    $textClasses = [
        $textColor,
        $textSize,
        $textFontWeight,
        'text-pretty',
    ];

    // If a div is editable but does not contain an id
    if($editable && !$id){
        throw new Exception("Editable elements require an id");
    }

    // If the div is transparent or not, add eiter default or special classes. By default color = zijpalm
    if($color == 'transparent'){
        array_push($divClasses, 'w-max', 'max-w-dvw', 'drop-shadow-[0_2.0px_3.0px_rgba(0,0,0,1)]', 'px-6', '-mt-2', 'md:-mt-4', '-mb-5', 'break-words');
        array_push($titleClasses, 'md:text-5xl', 'w-full', 'px-3', 'text-center');
    }
    else{
        array_push($divClasses, 'bg-linear-to-t', 'inset-shadow-sm', 'shadow-md', 'rounded-2xl', 'duration-1000');
        if(!isset($width)){
            array_push($titleClasses, 'w-5/6', 'mx-auto');
        }
        array_push($titleClasses, 'text-center');
    }

    // If it is a form, default to 'very wide' on large displays
    if($form){
        array_push($divClasses, 'xl:w-11/12');
    }
    else{
        if($width === null && $color !== 'transparent'){
            array_push($divClasses, 'w-full lg:w-5/6');
        }
    }

    // For checking given $color value and applying the appropriate colour theme
    if($color == 'zijpalm'){
        array_push($divClasses, 'from-zijpalm-700','to-zijpalm-500','inset-shadow-zijpalm-400', 'text-zinc-100');
    }

    if($color == 'light'){
        array_push($divClasses, 'from-zinc-300','to-zinc-200','inset-shadow-zinc-100');

        // Otherwise add default contrasting colour
        if(!$error && !$success){
            array_push($divClasses, 'text-zijpalm-700');
        }
    }

    if ($success) {
        array_push($divClasses, 'text-green-500', 'fixed', 'z-999');
    }

    // If div is for displaying errors, add RED text, fix it on screen and make sure it's on top
    if($error){
        array_push($divClasses, 'text-red-500', 'fixed', 'z-999');
    }
    // By default, make it relative (so the edit-content cog is displayed properly)
    array_push($divClasses, $display ?? 'relative');
//    else{
//    }

@endphp

<div {{$attributes->merge(['class' => implode(' ', $divClasses)])}}>
    {{-- Edit content cogwheel, only display if content is editable --}}
    @if($editable)
        <x-edit-content :$id :$name :$editables/>
    @endif

    {{-- Title --}}
    @if($title)
        <p @class($titleClasses)>{{$title}}</p>
    @endif

    {{-- Text or Link, depending on $textIsLink boolean, content and classes are shared --}}
    @if($text)
        @if($textIsLink)
            <a href="{{$href}}" @class($textClasses)>{!!$text!!}</a>
        @else
            <p @class($textClasses)>{!!$text!!}</p>
        @endif
    @endif

    {{-- Accept any other item --}}
    {{$slot}}
</div>
