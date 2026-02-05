@props(['container' => null])

@php
$classes = Flux::classes('[grid-area:main]')
    ->add('bg-cover bg-no-repeat bg-top') // Background image styling
    ->add('[[data-flux-container]_&]:px-0') // If there is a wrapping container, let IT handle the x padding...
    ->add($container ? 'mx-auto w-full [:where(&)]:max-w-7xl' : '');
@endphp

<div {{$attributes->class($classes)}} data-flux-main>
    {{$slot}}
</div>