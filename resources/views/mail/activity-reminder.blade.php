{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
<x-layouts.mail.header :user="$user" :hideGreeting="true">
    <p>Beste leden en (eventuele) introducees,</p>

    {!! $content->textHTML !!}

    <h3>{{$activity->title}}</h3>

    {!! $description ?? "" !!}

    <p>
        <strong>Wanneer:</strong> {{ formatDate($activity->start)}}<br>
        <strong>Locatie:</strong> {{ $activity->location }}<br>
        <strong>Organisator(en):</strong> {{$activity->organizer}}
    </p>

    <x-mail.button :href="route('activity.show', $activity)" label="Bekijk Activiteit"/>
</x-layouts.mail.header>
