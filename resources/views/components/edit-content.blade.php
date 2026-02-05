

{{-- Props --}}
@props([
    'invert' => false,
    'id' => null,
    'name' => null,
    'editables' => ['Geen'], //Default = None
])

{{-- Logic --}}
@php
    if(!isset($id)){
        throw new Exception("Variable 'id' is required");
    }
    else{
        // Do nothing, maybe cry
    }

    if(!auth()->check()){
        return;
    }

    if(auth()->check() && !auth()->user()->is_admin){
        return;
    }
@endphp

{{-- Custom CSS styling for entertaining spinning motion --}}
<style>
    .spin:hover {
        animation: spin 3.5s linear infinite;
    }
</style>

{{-- Component --}}
{{-- TO DO: Add Route to content editor, based on $id --}}
<flux:tooltip>
    <a id="content-{{$id}}" href="{{route('content.edit', $id)}}" @class(['absolute', 'top-2', 'right-2', 'z-10', 'cursor-pointer'])>
        <flux:icon.cog-6-tooth @class(['invert' => $invert, 'hover:scale-110', 'spin'])/>
    </a>
    <flux:tooltip.content class="text-center">
        {{-- Convert name to readable text for display --}}
        <p> Naam: {{kebab_to_display($name)}} </p>
        {{-- Shows array elements seperated by commas --}}
        <p> {{$editables ? 'Bewerkbare Items: ' . implode(', ', (array)$editables) : ''}} </p>
    </flux:tooltip.content>
</flux:tooltip>
