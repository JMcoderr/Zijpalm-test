<x-layouts.mail.header :user="$user">
    {!! $content->textHTML !!}
    <p>{{$activity->title}}</p>
</x-layouts.mail.header>