{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
@props([
    'status',
])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-green-600']) }}>
        {{ $status }}
    </div>
@endif
