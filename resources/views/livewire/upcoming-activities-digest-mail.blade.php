<div>
    <div class="flex flex-col">
        <p class="mb-4 text-gray-900">
            Met deze actie verstuurt u een mail met alle toekomstige activiteiten naar alle actieve leden.<br>
            Dit kan enige tijd duren afhankelijk van het aantal leden en de wachttijd tussen mails.
        </p>

        <form id="upcoming-activities-digest-form" method="POST" action="{{ route('activity.sendUpcomingActivitiesDigest') }}" class="flex flex-col gap-4" onsubmit="const submitButton = this.querySelector('button'); if (submitButton) { submitButton.disabled = true; const buttonLabel = submitButton.querySelector('p'); if (buttonLabel) { buttonLabel.innerText = 'Bezig met versturen...'; } }">
            @csrf
            <x-zijpalm-button type="submit" form="upcoming-activities-digest-form" label="Mail toekomstige activiteiten versturen" center="horizontal"/>
        </form>
    </div>
</div>
