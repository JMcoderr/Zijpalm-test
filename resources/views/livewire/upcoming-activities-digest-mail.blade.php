{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
<div>
    @php
        $digestActivities = \App\Models\Activity::query()
            ->whereNotNull('start')
            ->where('start', '>=', now()->startOfDay())
            ->where('type', '!=', \App\ActivityType::Cancelled)
            ->where('registrationStart', '<=', now())
            ->where('registrationEnd', '>=', now())
            ->orderBy('start')
            ->get();
    @endphp
    <div class="flex flex-col">
        {{-- Show errors, if any --}}
        @if((isset($componentErrors) && !empty($componentErrors)) || (isset($errors) && method_exists($errors,'any') && $errors->any()))
            <x-zijpalm-div color="light" title="Foutmelding(en)" :editable="false" error id="error-messages"
                           onclick="this.remove()">
                <ul class="text-center">
                    @if(isset($componentErrors) && !empty($componentErrors))
                        @foreach($componentErrors as $error)
                            <li class="">{{ $error }}</li>
                        @endforeach
                    @endif
                    @if(isset($errors) && method_exists($errors,'all'))
                        @foreach($errors->all() as $error)
                            <li class="">{{ $error }}</li>
                        @endforeach
                    @endif
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

        <p class="mb-4 text-gray-900">
            Met deze actie verstuurt u direct een mail met alle toekomstige activiteiten naar de ingestelde ontvangers.
        </p>

        <form id="upcoming-activities-digest-form" method="POST" action="{{ route('activity.sendUpcomingActivitiesDigest') }}">
            @csrf
            <x-input-group grid grid="grid grid-cols-1 grid-rows-[auto] auto-rows-auto">
                <x-input-group grid="grid grid-cols-1">
                        <x-input-field id="batch_size" name="batch_size" label="Hoeveelheid ontvangers in de BCC per mail" type="number"
                                       value="{{ old('batch_size', $batch_size ?? config('mail.power_automate.batch_size.default')) }}"
                                       :min="config('mail.power_automate.batch_size.min')"
                                       :max="config('mail.power_automate.batch_size.max')" required/>
                        <x-input-field id="delay" name="delay" label="Wachttijd tussen mails in seconden" type="number"
                                       value="{{ old('delay', $delay ?? config('mail.power_automate.delay.default')) }}"
                                       :min="config('mail.power_automate.delay.min')"
                                       :max="config('mail.power_automate.delay.max')" required/>
                    <div class="mt-2 text-sm text-gray-700">
                        <p>Ontvangers: <span id="upcoming-digest-recipient-count">{{ $recipientCount ?? 0 }}</span></p>
                        <p>Geschatte duur: <span id="upcoming-digest-estimate">-</span></p>
                    </div>

                    <div class="mt-4">
                        <p class="font-semibold text-gray-900 mb-2">Welke activiteiten wilt u opnemen in de mail?</p>
                        <p class="text-sm text-gray-700 mb-2">Laat alles aangevinkt om alle activiteiten mee te sturen.</p>
                        <div class="max-h-56 overflow-y-auto border border-gray-300 rounded p-2 bg-zinc-50">
                            @forelse($digestActivities as $activity)
                                <label class="flex items-start gap-2 py-1">
                                    <input type="checkbox" name="activity_ids[]" value="{{ $activity->id }}" checked>
                                    <span class="text-sm text-gray-900">
                                        <strong>{{ $activity->title }}</strong>
                                        <span class="text-gray-700">({{ formatDate($activity->start) }})</span>
                                    </span>
                                </label>
                            @empty
                                <p class="text-sm text-gray-700">Geen beschikbare activiteiten gevonden binnen de huidige selectie.</p>
                            @endforelse
                        </div>
                    </div>

                    <x-zijpalm-button form="upcoming-activities-digest-form" type="submit" label="Mail toekomstige activiteiten versturen"
                                      center="horizontal" class="mt-2"/>
                </x-input-group>
            </x-input-group>
        </form>
        <script>
            (function(){
                // Update display estimate and persist settings when modal closes
                const form = document.getElementById('upcoming-activities-digest-form');
                const batchInput = form?.querySelector('#batch_size');
                const delayInput = form?.querySelector('#delay');
                const recipients = parseInt(document.getElementById('upcoming-digest-recipient-count')?.innerText, 10) || 0;
                const estimateEl = document.getElementById('upcoming-digest-estimate');

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

                // Listen for a request to close; ask to save and persist settings, then ack to close the modal.
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
