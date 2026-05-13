{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
@props([
    'variant' => null,
])

@php
    $classes = Flux::classes()->add('flex flex-col gap-1')->add('overflow-visible min-h-auto min-w-max');
@endphp

<nav {{ $attributes->class($classes) }} data-flux-navlist>
    {{ $slot }}
</nav>
