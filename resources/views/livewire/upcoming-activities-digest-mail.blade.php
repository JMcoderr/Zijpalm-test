<div>
    <div class="flex flex-col">
        <p class="mb-4 text-gray-900">
            Met deze actie verstuurt u een mail met alle toekomstige activiteiten naar alle actieve leden.<br>
            Dit kan enige tijd duren afhankelijk van het aantal leden en de wachttijd tussen mails.
        </p>

        <form id="upcoming-activities-digest-form" method="POST" action="{{ route('activity.sendUpcomingActivitiesDigest') }}" class="flex flex-col gap-4" onsubmit="this.querySelector('button[type=submit]').disabled = true; this.querySelector('button[type=submit] p').innerText = 'Bezig met versturen...';">
            @csrf
            <x-zijpalm-button type="submit" form="upcoming-activities-digest-form" label="Mail toekomstige activiteiten versturen" center="horizontal"/>
        </form>
    </div>
</div>
