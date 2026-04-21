<x-layouts.mail.header :user="$user">
    <p><strong>Let op:</strong> de link staat hieronder als gewone tekst. Kopieer de link en plak die in de adresbalk van je browser om de activiteit te openen.</p>

    {!! $introHtml !!}

    @if($runningActivities->isNotEmpty())
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

    <p><strong>Komende activiteiten:</strong></p>

    <ul>
        @foreach($activities as $activity)
            <li style="margin-bottom: 16px;">
                <strong>{{ $activity->title }}</strong><br>
                {{ formatDate($activity->start) }}
                @if($activity->location)
                    - {{ $activity->location }}
                @endif
                <br>
                <span style="white-space: nowrap;">zijpalm.nl/activiteiten/{{ $activity->id }}</span>
            </li>
        @endforeach
    </ul>
</x-layouts.mail.header>
