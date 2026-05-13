{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
<x-layouts.mail.header :user="$user">
    {!! $content->textHTML !!}
    <p>{{$activity->title}}</p>
</x-layouts.mail.header>