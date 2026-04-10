{!! $introHtml !!}

@if($runningActivities->isNotEmpty())
    <p><strong>Lopende activiteiten:</strong></p>
    <ul>
        @foreach($runningActivities as $activity)
            <li>
                <a href="{{ route('activity.show', $activity) }}">{{ $activity->title }}</a>
            </li>
        @endforeach
    </ul>
@endif

<p><strong>Komende activiteiten:</strong></p>

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
