{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
@props([
    'scrollable' => false,
    'variant' => null,
])

@php
$classes = Flux::classes()
    ->add('flex items-center gap-1 py-3')
    ->add($scrollable ? ['overflow-x-auto overflow-y-hidden'] : [])
    ;
@endphp

<nav {{ $attributes->class($classes) }} data-flux-navbar>
    {{ $slot }}
</nav>
