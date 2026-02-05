<x-layouts.mail.header :user="$user">
    {!! $content->textHTML !!}

    <h3>{{$activity->title}}</h3>

    {!! $description ?? "" !!}
    <p>U kunt inschrijven voor deze activiteit vanaf {{formatDate($activity->registrationStart)}} tot en met {{formatDate($activity->registrationEnd)}}

    <p>
        <strong>Startdatum- en tijd:</strong> {{ formatDate($activity->start) }} om {{ formatTime($activity->start) }} uur<br>
        <strong>Einddatum- en tijd:</strong> {{ formatDate($activity->end) }} om {{ formatTime($activity->end) }} uur<br>
        <strong>Locatie:</strong> {{ $activity->location }}<br>
        <strong>Organisator(en):</strong> {{$activity->organizer}}
    </p>

    <x-mail.button :href="route('activity.show', $activity)" label="Bekijk Activiteit"/>
</x-layouts.mail.header>
