<p>Beste leden,</p>

<p>Hieronder vinden jullie de komende activiteiten van Zijpalm:</p>

<ul>
    @foreach($activities as $activity)
        <li>
            <strong>{{ $activity->title }}</strong><br>
            {{ formatDate($activity->start) }} om {{ formatTime($activity->start) }} uur
            @if($activity->location)
                - {{ $activity->location }}
            @endif
            <br>
            <a href="{{ route('activity.show', $activity) }}">Bekijk activiteit</a>
        </li>
    @endforeach
</ul>

<p>Met vriendelijke groet,<br>
Zijpalm</p>
