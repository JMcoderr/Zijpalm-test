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
            Met deze actie verstuurt u een mail met alle toekomstige activiteiten naar alle actieve leden.<br>
            Dit kan enige tijd duren afhankelijk van het aantal leden en de wachttijd tussen mails.
        </p>

        <form id="upcoming-activities-digest-form" method="POST" action="{{ route('activity.sendUpcomingActivitiesDigest') }}" class="flex flex-col gap-4" onsubmit="const submitButton = this.querySelector('button'); if (submitButton) { submitButton.disabled = true; const buttonLabel = submitButton.querySelector('p'); if (buttonLabel) { buttonLabel.innerText = 'Bezig met versturen...'; } }">
            @csrf
            <x-input-group grid grid="grid grid-cols-1 grid-rows-[auto] auto-rows-auto">
                <x-input-group grid="grid grid-cols-1">
                    <x-input-field id="batch_size" label="Hoeveelheid ontvangers in de BCC per mail" type="number"
                                   :value="config('mail.power_automate.batch_size.default')"
                                   :min="config('mail.power_automate.batch_size.min')"
                                   :max="config('mail.power_automate.batch_size.max')" required/>
                    <x-input-field id="delay" label="Wachttijd tussen mails in seconden" type="number"
                                   :value="config('mail.power_automate.delay.default')"
                                   :min="config('mail.power_automate.delay.min')"
                                   :max="config('mail.power_automate.delay.max')" required/>
                    <x-zijpalm-button form="upcoming-activities-digest-form" type="submit" label="Mail toekomstige activiteiten versturen"
                                      center="horizontal" class="mt-2"/>
                </x-input-group>
            </x-input-group>
        </form>
    </div>
</div>
