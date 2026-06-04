{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
<x-layouts.mail.header :user="$user">
    {{-- This intro is generated in the PHP class and inserted here. --}}
    {!! $introHtml !!}

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
    <ul>
        @foreach($activities as $activity)
            <li style="margin-bottom: 12px;">
                <strong>{{ $activity->title }}</strong>
                <div>{{ $activity->type === \App\ActivityType::Weekly ? ucfirst((['maandag','dinsdag','woensdag','donderdag','vrijdag','zaterdag','zondag'][(intval((new \Illuminate\Support\Carbon($activity->start))->dayOfWeekIso ?? 1) - 1) ?? 0])) : formatDate($activity->start) }} @if($activity->location) - {{ $activity->location }}@endif</div>
                <div style="white-space: nowrap;">zijpalm.nl/activiteiten/{{ $activity->id }}</div>
            </li>
        @endforeach
    </ul>
</x-layouts.mail.header>
