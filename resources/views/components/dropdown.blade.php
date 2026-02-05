@props([
    'title' => null,
    'open' => false,
    'hasNestedDropdown' => false,
])

@php
    $classes = [
        'py-2'
    ];

    if($hasNestedDropdown) {
        array_push($classes, 'px-2 flex flex-col gap-y-2');
    } else {
        array_push($classes, 'px-4');
    }
@endphp

{{-- Sets the value of open based on php $open --}}
<div x-data="{ open: {{$open ? 'true' : 'false'}} }" class="bg-zinc-200 rounded-lg border border-black">
    <button @click="open = !open" class="w-full text-left px-4 py-2 text-2xl font-semibold flex justify-between items-center">
        <span>{{$title}}</span>
        <flux:icon name="chevron-down" class="ml-2" x-show="!open" x-cloak />
        <flux:icon name="chevron-up" class="ml-2" x-show="open" x-cloak />
    </button>
    {{-- Cloaks the slot if $open is false --}}
    <div x-show="open" x-transition {{!$open ? 'x-cloak' : ''}} {{$attributes->merge(['class' => implode(' ', $classes)])}}>
        {{$slot}}
    </div>
</div>