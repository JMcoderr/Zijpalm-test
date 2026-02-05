@props([
    // Expecting elements to create cards with, default to empty array
    'cards' => [],
    'cardType' => null,

    // Enable or disable transition animations, default is true
    'transitions' => true,

    // Any alpine props
    'alpine' => [],

    // Editable props
    'itemseditable' => false,
    'itemseditables' => [],
])

@php
    // Default style classes
    $cardHolderClasses = [
        'md:min-w-min',
        'w-full', 
        'flex',
        'flex-wrap',
        'justify-center',
        'gap-5',
        'items-stretch',
    ];

    // Transition attributes based on Alpine
    $transitionAttributes = [
        // Cloak
        'x-cloak' => true,
        'x-transition:enter' => 'transition ease-out duration-500',
        'x-transition:enter-start' => 'opacity-0 scale-90 translate-y-3',
        'x-transition:enter-end' => 'opacity-100 scale-100 translate-y-0',
        'x-transition:leave' => 'transition ease-in duration-400',
        'x-transition:leave-start' => 'opacity-100 scale-100 translate-y-0',
        'x-transition:leave-end' => 'opacity-0 scale-90 translate-y-3',
    ];

    // Merge classes
    $cardHolderAttributes = $attributes->merge(['class' => implode(' ', $cardHolderClasses)]);

    // If alpine attributes are given as props
    if(!empty($alpine)){
        $cardHolderAttributes = $cardHolderAttributes->merge($alpine);
    }

    // If transitions is true (default: true)
    if($transitions){
        $cardHolderAttributes = $cardHolderAttributes->merge($transitionAttributes);
    }
    
    // Throw tantrum if card is not of expected type
    in_array($cardType, ['activity','content', 'report']) || throw new Exception("Invalid card type: {$cardType}");
@endphp

<div {{$cardHolderAttributes}}>
    @foreach($cards as $card)
        <x-zijpalm-card :content="$card" :type="$cardType" :transitions="$transitionAttributes" :editable="$itemseditable" :editables="$itemseditables" />
    @endforeach
</div>