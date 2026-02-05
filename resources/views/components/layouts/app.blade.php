@props([
    'title' => '',
])

@php
    $background = \App\Models\Content::where('name', 'background')->first();

    // TO DO: Value should be changeable, different background images need different darkening (or not) after all
    $dim = 0.275;

    // Brightness value in percentages, used for displaying changes to background dimming
    // Possible inline conversion in 'brightness' slider
    $brightness = (1 - $dim) * 100;
@endphp

{{-- Bases title on <x-layouts.app title="thisTitle"> --}}
{{-- <x-layouts.app> is also featured in <x-page-wrapper> --}}
<x-layouts.app.header :$title>
    {{-- flux:main is the background / anything below the navigation bar --}}
    <flux:main style="background-image: url({{$background->file}})" class="relative">
        @if(auth()->user()?->is_admin)
            <div class="bg-linear-to-t from-zinc-300 to-zinc-100 inset-shadow-sm inset-shadow-50 hidden absolute lg:block top-0 right-0 size-10.5 rounded-bl-2xl">
                <x-edit-content :id="$background->id" :name="$background->name" :editables="['Afbeelding']"/>
            </div>
        @endif
        {{-- Below <div> adds a darkening overlay to the background to increase readability --}}
        <div class="h-full w-full p-4 lg:p-6" style="background-color: rgba(0,0,0,{{$dim}})">
            {{$slot}}
        </div>
    </flux:main>
</x-layouts.app.header>
