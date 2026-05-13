{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
<div>
    <div class="flex flex-col">
        {{-- Show errors, if any --}}
        @if(!empty($errors))
            <x-zijpalm-div color="light" title="Foutmelding(en)" :editable="false" error id="error-messages"
                           onclick="this.remove()">
                <ul class="text-center">
                    @foreach($errors as $error)
                        <li class="">{{$error}}</li>
                    @endforeach
                </ul>
            </x-zijpalm-div>
            <script>
                setTimeout(function () {
                    const errorDiv = document.getElementById('error-messages');
                    if (errorDiv) {
                        errorDiv.remove();
                    }
                }, 5000);
            </script>
        @endif

        <form id="reminder-mail-form" method="POST" action="{{route('activity.notifyParticipants', $activity)}}">
            @CSRF
            <x-input-group grid grid="grid grid-cols-1 grid-rows-[18rem] auto-rows-auto">
                <x-input-group class="items-stretch">
                    <x-input-field id="description" label="Beschrijving" type="editor" required/>
                </x-input-group>
                <x-input-group grid="grid grid-cols-1">
                    <x-input-field id="batch_size" label="Hoeveelheid ontvangers in de BCC per mail" type="number"
                                   value="{{ old('batch_size', $batch_size ?? config('mail.power_automate.batch_size.default')) }}"
                                   :min="config('mail.power_automate.batch_size.min')"
                                   :max="config('mail.power_automate.batch_size.max')" required/>
                    <x-input-field id="delay" label="Wachttijd tussen mails in seconden" type="number"
                                   value="{{ old('delay', $delay ?? config('mail.power_automate.delay.default')) }}"
                                   :min="config('mail.power_automate.delay.min')"
                                   :max="config('mail.power_automate.delay.max')" required/>
                    <div class="mt-2 text-sm text-gray-700">
                        <p>Ontvangers: <span id="reminder-recipient-count">{{ $recipientCount ?? 0 }}</span></p>
                        <p>Geschatte duur: <span id="reminder-estimate">-</span></p>
                    </div>
                    <x-zijpalm-button form="reminder-mail-form" type="submit" label="Verstuur herinnering"
                                      center="horizontal" class="mt-2"/>
                </x-input-group>
            </x-input-group>
        </form>
        <script>
            (function(){
                // English comment: update display estimate when inputs change and persist settings when modal closes
                const batchInput = document.getElementById('batch_size');
                const delayInput = document.getElementById('delay');
                const recipients = parseInt(document.getElementById('reminder-recipient-count').innerText, 10) || 0;
                const estimateEl = document.getElementById('reminder-estimate');

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

                // English comment: listen for a request to close; ask the user to confirm saving, persist settings, then ack to close the modal.
                window.addEventListener('zijpalm-modal-request-close', async function (ev) {
                    if (ev?.detail?.modal !== 'reminderMailModal') return;

                    // Ask the user whether to save settings before closing.
                    const shouldSave = confirm('Wil je de instellingen opslaan voordat je sluit? (OK = opslaan, Annuleren = sluiten zonder opslaan)');

                    if (!shouldSave) {
                        // user opted not to save — acknowledge close so the modal will close
                        window.dispatchEvent(new CustomEvent('zijpalm-modal-close-ack', {detail: {modal: 'reminderMailModal'}}));
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
                                name: 'reminder',
                                batch_size: parseInt(batchInput.value, 10) || null,
                                delay: parseInt(delayInput.value, 10) || null,
                            })
                        });
                    } catch (e) {
                        // ignore save errors but still proceed to close — optionally inform the user
                        console.warn('Could not save reminder settings', e);
                    }

                    // After save (or failure) acknowledge the close so the modal component actually hides
                    window.dispatchEvent(new CustomEvent('zijpalm-modal-close-ack', {detail: {modal: 'reminderMailModal'}}));
                });

                batchInput.addEventListener('input', updateEstimate);
                delayInput.addEventListener('input', updateEstimate);
                updateEstimate();
            })();
        </script>
    </div>
</div>
