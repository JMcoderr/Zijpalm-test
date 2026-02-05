@props([
    'variant' => null,
])

@php
    $classes = Flux::classes()->add('flex flex-col gap-1')->add('overflow-visible min-h-auto min-w-max');
@endphp

<nav {{ $attributes->class($classes) }} data-flux-navlist>
    {{ $slot }}
</nav>
