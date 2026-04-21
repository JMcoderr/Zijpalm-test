<div>
    <div class="flex flex-col">
        @if($errors->any())
            <x-zijpalm-div color="light" title="Foutmelding(en)" :editable="false" error id="upcoming-digest-error-messages">
                <ul class="text-center">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-zijpalm-div>
            <script>
                setTimeout(function () {
                    const errorDiv = document.getElementById('upcoming-digest-error-messages');
                    if (errorDiv) {
                        errorDiv.remove();
                    }
                }, 5000);
            </script>
        @endif

        <p class="mb-4 text-gray-900">
            Met deze actie verstuurt u direct een mail met alle toekomstige activiteiten naar de ingestelde ontvangers.<br>
        </p>

        <form id="upcoming-activities-digest-form" method="POST" action="{{ route('activity.sendUpcomingActivitiesDigest') }}" class="flex flex-col gap-4" onsubmit="const submitButton = this.querySelector('button'); if (submitButton) { submitButton.disabled = true; const buttonLabel = submitButton.querySelector('p'); if (buttonLabel) { buttonLabel.innerText = 'Bezig met versturen...'; } }">
            @csrf
            <x-zijpalm-button form="upcoming-activities-digest-form" type="submit" label="Mail toekomstige activiteiten versturen" center="horizontal" class="mt-2"/>
        </form>
    </div>
</div>
