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
            Met deze actie verstuurt u direct een mail met alle toekomstige activiteiten naar de ingestelde ontvangers.
        </p>

        <form id="upcoming-activities-digest-form" method="POST" action="{{ route('activity.sendUpcomingActivitiesDigest') }}" onsubmit="const submitButton = this.querySelector('button'); if (submitButton) { submitButton.disabled = true; const buttonLabel = submitButton.querySelector('p'); if (buttonLabel) { buttonLabel.innerText = 'Bezig met versturen...'; } }">
            @csrf
            <x-input-group grid grid="grid grid-cols-1 grid-rows-[auto] auto-rows-auto">
                <x-input-group grid="grid grid-cols-1">
                    <x-input-field id="batch_size" label="Aantal ontvangers per mail (batch size)" type="number"
                                   value="{{ old('batch_size', $batch_size ?? config('mail.power_automate.batch_size.default')) }}"
                                   :min="config('mail.power_automate.batch_size.min')"
                                   :max="config('mail.power_automate.batch_size.max')" required/>
                    <x-input-field id="delay" label="Wachttijd tussen batches (seconden)" type="number"
                                   value="{{ old('delay', $delay ?? config('mail.power_automate.delay.default')) }}"
                                   :min="config('mail.power_automate.delay.min')"
                                   :max="config('mail.power_automate.delay.max')" required/>
                    <div class="mt-2 text-sm text-gray-700 text-center">
                        <p>Ontvangers: <span id="digest-recipient-count">{{ $recipientCount ?? 0 }}</span></p>
                        <p>Geschatte duur: <span id="digest-estimate">-</span></p>
                    </div>
                    <x-zijpalm-button form="upcoming-activities-digest-form" type="submit" label="Mail toekomstige activiteiten versturen"
                                      center="horizontal" class="mt-2"/>
                </x-input-group>
            </x-input-group>
        </form>
        <script>
            (function(){
                // English comment: update display estimate and persist digest settings when modal closes
                const batchInput = document.getElementById('batch_size');
                const delayInput = document.getElementById('delay');
                const recipients = parseInt(document.getElementById('digest-recipient-count').innerText, 10) || 0;
                const estimateEl = document.getElementById('digest-estimate');

                function updateEstimate(){
                    const batch = Math.max(1, parseInt(batchInput.value, 10) || 1);
                    const delay = Math.max(0, parseInt(delayInput.value, 10) || 0);
                    const batches = Math.ceil(recipients / batch);
                    const seconds = batches * delay;
                    if(recipients === 0){
                        estimateEl.innerText = '0s (no recipients)';
                        return;
                    }
                    const mins = Math.floor(seconds / 60);
                    const secs = seconds % 60;
                    estimateEl.innerText = mins > 0 ? `${mins}m ${secs}s` : `${secs}s`;
                }

                // English comment: listen for a request to close; confirm and persist digest settings, then ack to close the modal.
                window.addEventListener('zijpalm-modal-request-close', async function (ev) {
                    if (ev?.detail?.modal !== 'upcomingActivitiesDigestMailModal') return;

                    const shouldSave = confirm('Wil je de instellingen opslaan voordat je sluit? (OK = opslaan, Annuleren = sluiten zonder opslaan)');
                    if (!shouldSave) {
                        window.dispatchEvent(new CustomEvent('zijpalm-modal-close-ack', {detail: {modal: 'upcomingActivitiesDigestMailModal'}}));
                        return;
                    }

                    try {
                        await fetch("{{ route('activity.mail-settings.store') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            },
                            body: JSON.stringify({
                                name: 'digest',
                                batch_size: parseInt(batchInput.value, 10) || null,
                                delay: parseInt(delayInput.value, 10) || null,
                            })
                        });
                    } catch (e) {
                        console.warn('Could not save digest settings', e);
                    }

                    window.dispatchEvent(new CustomEvent('zijpalm-modal-close-ack', {detail: {modal: 'upcomingActivitiesDigestMailModal'}}));
                });

                if(batchInput && delayInput){
                    batchInput.addEventListener('input', updateEstimate);
                    delayInput.addEventListener('input', updateEstimate);
                    updateEstimate();
                }
            })();
        </script>
    </div>
</div>
