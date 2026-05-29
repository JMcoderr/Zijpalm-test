{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
<x-layouts.mail.header :user="$user">
    {{-- This intro is generated in the PHP class and inserted here. --}}
    {!! $introHtml !!}

    {{-- Show the batch settings so the email can be debugged if needed. --}}
    <p><strong>Batch size:</strong> {{ $batch_size }}</p>
    <p><strong>Delay (seconds):</strong> {{ $delay }}</p>

    {{-- Explain that the activity link is plain text on purpose. --}}
    <p><strong>Let op:</strong> de link staat hieronder als gewone tekst. Kopieer de link en plak die in de adresbalk van je browser om de activiteit te openen. Of ga naar de website zijpalm.nl</p>

    @if($runningActivities->isNotEmpty())
        {{-- Show activities that are already running right now. --}}
        <p><strong>Lopende activiteiten:</strong></p>
        <ul>
            @foreach($runningActivities as $activity)
                <li style="margin-bottom: 16px;">
                    <strong>{{ $activity->title }}</strong><br>
                    <span style="white-space: nowrap;">zijpalm.nl/activiteiten/{{ $activity->id }}</span>
                </li>
            @endforeach
        </ul>
    @endif

    {{-- Show the upcoming activities that people can still sign up for. --}}
    <p>Hieronder een overzicht van de komende activiteiten waarvoor je je kan inschrijven. Of ga naar onze site: zijpalm.nl</p>

    <ul>
        @foreach($activities as $activity)
            <li style="margin-bottom: 16px;">
                <strong>{{ $activity->title }}</strong><br>
                @if($activity->type === \App\ActivityType::Weekly)
                    @php
                        $dayNames = ['maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag', 'zondag'];
                        $dayIndex = (intval((new \Illuminate\Support\Carbon($activity->start))->dayOfWeekIso) ?? 1) - 1;
                    @endphp
                    {{ ucfirst($dayNames[$dayIndex] ?? '') }}
                @else
                    {{ formatDate($activity->start) }}
                @endif
                @if($activity->location)
                    - {{ $activity->location }}
                @endif
                <br>
                <span style="white-space: nowrap;">zijpalm.nl/activiteiten/{{ $activity->id }}</span>
            </li>
        @endforeach
    </ul>
</x-layouts.mail.header>
