<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}">

{{-- Title logic handled in 'header.blade.php' --}}
@isset($title)
    <title>{{$title}}</title>
    @else
    <title>{{config('app.name')}}</title>
@endisset

@vite(['resources/css/app.css', 'resources/js/app.js'])

{{-- Add stack to push scripts or styles into head --}}
@stack('scripts')
@stack('styles')
