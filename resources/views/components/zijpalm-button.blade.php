{{-- Props --}}
@props([
    'id' => null,
    'href' => route('home'),
    'label' => 'button', // Text on the button
    'variant' => 'default', // Style of the button
    'type' => 'redirect', // Purpose of the button
    'target' => '_self',
    'icon' => null,
    'center' => null,
    'form' => null,
    'onclick' => null,
    'wireclick' => null,
    'size' => 'size-6',
    'margin' => null,
    'position' => null,
    'span' => null,
    'align' => null,
])

@php
    // Classes, seperated for clarity as there are quite a few
    $classes = [
        'bg-linear-to-t',
        'inset-shadow-sm',
        'shadow-md',
        'rounded-full',
        'focus:scale-105',
        'focus:brightness-105',
        'hover:scale-105',
        'hover:brightness-105',
        'duration-300',
        'text-nowrap',
        'font-medium',
        'cursor-pointer',
        'outline-0',
        'size-fit',
        $margin,
        $position,
        $span,
    ];

    $labelClasses = [
        'duration-600',
    ];

    $iconClasses = [
        'duration-600',
        'stroke-3',
        $size,
    ];

    // Overview of variants, updated if new ones are added
    $variants = [
        'default',
        'obvious',
        'add',
        'subtract',
        'remove',
        'forward',
        'backward',
        'close',
    ];

    // Overview of types, updated if new ones are added
    $types = [
        'redirect',
        'submit',
        'action',
    ];

    // While this does correctly add classes based on the given ID, Tailwind's compiler doesn't apply them in time
    // Solution: Create either a component that's not just a view or a Livewire component to calculate dynamic classes before rendering
    // if($id){
    //     array_push($classes, 'group/'.$id);
    //     array_push($labelClasses, 'group-hover/'.$id.':scale-110');
    //     array_push($iconClasses, 'group-hover/'.$id.':scale-115');
    // }

    // Check if variant is valid
    if(!in_array($variant, $variants)){
        throw new Exception("Invalid button variant");
    }

    // Check if variant is valid if type is action
    // if($type == 'action' && !in_array($variant, ['add', 'subtract', 'remove', 'default'])){
    //     throw new Exception("Invalid button variant");
    // }

    // Handle empty $type by defaulting to redirect
    if($type === null || $type === ''){
        $type = 'redirect';
    }

    // Check if type is valid
    if(!in_array($type, $types)){
        throw new Exception("Invalid button type");
    }

    // Check if a submit actually includes a form
    if($type == 'submit' && !$form){
        throw new Exception("Submit buttons must include form");
    }

    // If variant is remove, change colours
    if($variant == 'remove'){
        array_push($classes, 'from-zinc-300','to-zinc-200','inset-shadow-zinc-100', 'text-red-500');
    }
    // Otherwise, use default colours
    else{
        array_push($classes, 'from-zijpalm-600', 'to-zijpalm-400', 'inset-shadow-zijpalm-300', 'text-zinc-100');
    }

    // BIGGER, BETTER, BELlIGERENTLY BEAUTIFUL BOLDNESS
    if($variant == 'obvious'){
        array_push($classes, 'py-2', 'px-4');
        array_push($labelClasses, 'text-2xl', 'font-bold');
    }
    
    // If variant is default (expecting a label), add padding
    if($type === 'action' && $variant == 'default'){
        array_push($classes, 'py-1.5', 'px-3.5', 'flex', 'size-fit');
    }

    // If the variant is anthing but default, add unilateral padding
    if($type === 'action' && $variant != 'default'){
        array_push($classes, 'p-1');
    }

    // If type isn't action one of the above, fit to size and add default padding
    if($type !== 'action'){
        array_push($classes, 'flex', 'size-fit', 'py-1.5', 'px-3.5');
    }

    // Auto-margin/Center based on given value, or all if none given
    if($center == 'horizontal'){
        array_push($classes, 'mx-auto');
    }
    if($center == 'vertical'){
        array_push($classes, 'my-auto');
    }
    if($center == 'all'){
        array_push($classes, 'm-auto');
    }
@endphp

{{-- By default, a redirect --}}
@if($type == 'redirect')
    <a href="{{$href}}" target="{{$target}}" {{$attributes->merge(['class' => implode(' ', $classes)])}}>
        @if($label)
            <p @class($labelClasses)>{{$label}}</p>
        @endif
    </a>
@endif

{{-- If button is a submit (therefore for a form) --}}
@if($type == 'submit')
    <button {{$attributes->merge(['class' => implode(' ', $classes)])}} form='{{$form}}' onclick='{{$onclick}}'>
        @if($label)
            <p @class($labelClasses)>{{$label}}</p>
        @endif
    </button>
@endif

{{-- Dynamic icons (<flux:icon.{{$icon}}/>) are not possible --}}
{{-- Saving them as strings and calling upon them later does not work --}}
{{-- Variants are to be added as shown below --}}

{{-- If button triggers an action --}}
@if($type == 'action')
    {{-- Prevent default actions, such as buttons inside a form submitting --}}
    <button {{$attributes->merge(['class' => implode(' ', $classes)])}} onclick='event.preventDefault(); {{$onclick}}' wire:click="{{$wireclick}}">
        {{-- If a non-default variant was given --}}
        {{-- Add a + sign --}}
        @if($variant == 'add')
            <flux:icon.plus @class($iconClasses)/>
        @endif

        {{-- Add a - sign --}}
        @if($variant == 'subtract')
            <flux:icon.minus @class($iconClasses)/>
        @endif   

        {{-- Add an x --}}
        @if($variant == 'remove' || $variant == 'close')
            <flux:icon.x-mark @class($iconClasses)/>
        @endif   

        {{-- Add the label --}}
        @if($variant == 'default' || $variant == 'obvious')
            <p @class($labelClasses)>{{$label}}</p>
        @endif

        {{-- Forward arrow --}}
        @if($variant == 'forward')
            <flux:icon.arrow-right @class($iconClasses)/>
        @endif

        {{-- Backward arrow --}}
        @if($variant == 'backward')
            <flux:icon.arrow-left @class($iconClasses)/>
        @endif   
    </button>
@endif