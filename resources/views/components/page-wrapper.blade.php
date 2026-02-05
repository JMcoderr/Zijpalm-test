@props([
    'page' => null,
])

@php
    if(!isset($page)){
        throw new Exception("Valid page is required for <x-page-wrapper>");
    }

    $classes = [
        'h-full',
        'w-full',
        'rounded ',
        'text-center',
        'text-white',
        'flex',
        'flex-col',
        'gap-y-5',
    ];
@endphp

{{-- Given page is used in Page Title --}}
<x-layouts.app :title="$page">
    <div {{$attributes->merge(['class' => implode(' ', $classes)])}}>
        @if (session('success'))
            <x-zijpalm-div color="light" :editable=false :text="session('success')" width="w-max" success/>
        @elseif(session('error'))
            <x-zijpalm-div color="light" :editable=false :text="session('error')" width="w-max" error/>
        @endif
        {{$slot}}
    </div>
</x-layouts.app>
