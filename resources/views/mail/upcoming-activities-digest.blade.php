<x-layouts.mail.header :user="$user">
    {!! $introHtml !!}

    <p><strong>Batch size:</strong> {{ $batch_size }}</p>
    <p><strong>Delay (seconds):</strong> {{ $delay }}</p>

    <p><strong>Let op:</strong> de link staat hieronder als gewone tekst. Kopieer de link en plak die in de adresbalk van je browser om de activiteit te openen. Of ga naar de website zijpalm.nl</p>

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
