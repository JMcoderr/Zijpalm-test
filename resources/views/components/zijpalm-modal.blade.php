@props([
    'id' => null,
    'width' => 'min-w-[55dvw] max-w-[90dvw]',
    'title' => null,
    'text' => null,
    'include' => null,
    'livewire' => false,
    'modal' => 'modalOpen',
    'variables' => [],
])

@php
    $backgroundClasses = [
        'fixed',
        'inset-0',
        'bg-[rgba(0,0,0,0.5)]',
        'flex',
        'items-center',
        'justify-center',
        'z-50',
    ];

    $backgroundTransitionAttributes = [
        'x-transition' => 'transition-opacity duration-300',
    ];

    $backgroundAttributes = $attributes->merge(['class' => implode(' ', $backgroundClasses)], $backgroundTransitionAttributes);
@endphp

{{-- Background --}}
<div x-show="{{$modal}}" x-cloak x-effect="{{$modal}} && window.scrollTo({top: 0, behavior: 'smooth'})" x-init="$watch('{{$modal}}', v => { if(v) $nextTick(() => { if(typeof window.initializeEditorJsHolders === 'function') window.initializeEditorJsHolders(); }) })" x-transition.opacity.duration.500ms x-on:zijpalm-modal-close-ack.window="if($event.detail?.modal === '{{$modal}}') {{$modal}} = false" {{$backgroundAttributes}}>

    {{-- Modal --}}
    {{-- English comment: when clicking outside we also dispatch the closing event so code can save state before modal hides --}}
    <x-zijpalm-div :$title :$text color="light" titleFontSize="text-5xl" titleColor="text-zijpalm-400" textColor="text-zijpalm-400" textSize="text-2xl" :editable="false" :$width padding="p-4" x-on:click.away="$dispatch('zijpalm-modal-request-close', {modal: '{{$modal}}'})" class="top-10 md:top-0 max-h-[95dvh] overflow-y-auto scrollbar-container">
        {{-- English comment: dispatch a DOM event when the modal close button is clicked so callers can react (e.g. save settings). The modal will close only after an explicit ack event. --}}
        <x-zijpalm-button type="action" variant="close" x-on:click="$dispatch('zijpalm-modal-request-close', {modal: '{{$modal}}'})" class="fixed md:absolute top-2 right-2"/>
        <flux:separator/>
        @if($include)
            @if($livewire)
                @livewire($include, $variables)
            @else
                @include($include, $variables)
            @endif
        @else
            {{$slot}}
        @endif
    </x-zijpalm-div>
</div>

<style>
    .scrollbar-container{
        position: relative;
        border-radius: 0.75rem;
        scrollbar-color: rgba(20, 110, 160, 0.15) transparent;
    }
</style>
