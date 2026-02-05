@props([
    'stashable' => null,
    'sticky' => null,
])

@php
$classes = Flux::classes('[grid-area:sidebar]')
    // Default padding (p-4)
    ->add('z-1 flex flex-col gap-1 [:where(&)]:w-64 p-3 bg-linear-to-r from-zinc-200 to-zinc-100 inset-shadow-sm inset-shadow-zinc-50')
    ;

if ($sticky) {
    $attributes = $attributes->merge([
        'x-bind:style' => '{ position: \'sticky\', top: $el.offsetTop + \'px\', \'max-height\': \'calc(100dvh - \' + $el.offsetTop + \'px)\' }',
        'class' => 'max-h-dvh overflow-y-auto overscroll-contain',
    ]);
}

if ($stashable) {
    $attributes = $attributes->merge([
        'x-bind:data-stashed' => '! screenLg',
        'x-resize.document' => 'screenLg = window.innerWidth >= 1024',
        'x-init' => '$el.classList.add(\'-translate-x-full\'); $el.removeAttribute(\'data-mobile-cloak\'); $el.classList.add(\'transition-transform\')',
    ])->class([
        'max-lg:data-mobile-cloak:hidden',
        '[[data-show-stashed-sidebar]_&]:translate-x-0! lg:translate-x-0!',
        'z-20! data-stashed:left-0! data-stashed:fixed! data-stashed:top-0! data-stashed:min-h-dvh! data-stashed:max-h-dvh!'
    ]);
}
@endphp

@if ($stashable)
    <flux:sidebar.backdrop />
@endif

<div {{ $attributes->class($classes) }} x-data="{ screenLg: window.innerWidth >= 1024 }" data-mobile-cloak data-flux-sidebar>
    {{ $slot }}
</div>

