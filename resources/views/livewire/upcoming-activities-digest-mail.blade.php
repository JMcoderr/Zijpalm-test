{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
<div>
    @php
        $digestActivities = \App\Models\Activity::query()
            ->whereDoesntHave('shopProduct')
            ->where('end', '>', now())
            ->whereNotIn('type', [\App\ActivityType::Weekly, \App\ActivityType::Cancelled])
            ->get()
            ->sortBy('start')
            ->values();
        $digestProducts = \App\Models\Product::query()->forDigest()->orderBy('name')->get();
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
            Met deze actie verstuurt u een mail met de geselecteerde activiteiten en producten naar de ingestelde ontvangers.
        </p>

        <form id="upcoming-activities-digest-form" method="POST" action="{{ route('activity.sendUpcomingActivitiesDigest') }}">
            @csrf
            <input type="hidden" name="selection_submitted" value="1">
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

                    <section data-digest-selection class="mt-5 min-w-0 text-left" aria-labelledby="digest-selection-heading">
                        <h3 id="digest-selection-heading" class="mb-3 text-base font-semibold text-zijpalm-700">Selectie voor de mail</h3>
                        <div class="grid min-w-0 grid-cols-1 gap-4 md:grid-cols-2">
                            <fieldset data-digest-group="activities" class="min-w-0 overflow-hidden rounded-md border border-zinc-300 bg-zinc-50" aria-labelledby="digest-activities-heading">
                                <div class="flex items-center justify-between gap-3 border-b border-zinc-300 bg-zinc-100 px-3 py-2.5">
                                    <span id="digest-activities-heading" class="text-sm font-semibold text-zijpalm-700">Activiteiten</span>
                                    <span class="text-xs tabular-nums text-zinc-600">{{ $digestActivities->count() }}</span>
                                </div>
                                <div class="max-h-72 overflow-y-auto overscroll-contain">
                                    @forelse($digestActivities as $activity)
                                        <label class="flex min-h-12 cursor-pointer items-start gap-3 border-b border-zinc-200 px-3 py-3 last:border-b-0 hover:bg-zijpalm-50 focus-within:bg-zijpalm-50">
                                            <input type="checkbox" name="activity_ids[]" value="{{ $activity->id }}" @checked(!old('selection_submitted') || in_array($activity->id, old('activity_ids', []))) class="mt-1 size-4 shrink-0 cursor-pointer accent-zijpalm-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-zijpalm-500">
                                            <span class="min-w-0 flex-1">
                                                <span class="block text-sm font-medium leading-5 text-zinc-900 [overflow-wrap:anywhere]">{{ $activity->title }}</span>
                                                <span class="mt-0.5 block text-xs leading-5 text-zinc-600">{{ formatDate($activity->start) }}</span>
                                            </span>
                                        </label>
                                    @empty
                                        <p class="px-3 py-4 text-sm text-zinc-600">Geen aankomende activiteiten gevonden.</p>
                                    @endforelse
                                </div>
                            </fieldset>
                            <fieldset data-digest-group="products" class="min-w-0 overflow-hidden rounded-md border border-zinc-300 bg-zinc-50" aria-labelledby="digest-products-heading">
                                <div class="flex items-center justify-between gap-3 border-b border-zinc-300 bg-zinc-100 px-3 py-2.5">
                                    <span id="digest-products-heading" class="text-sm font-semibold text-zijpalm-700">Shop / Overig</span>
                                    <span class="text-xs tabular-nums text-zinc-600">{{ $digestProducts->count() }}</span>
                                </div>
                                <div class="max-h-72 overflow-y-auto overscroll-contain">
                                    @forelse($digestProducts as $product)
                                        <label class="flex min-h-12 cursor-pointer items-start gap-3 border-b border-zinc-200 px-3 py-3 last:border-b-0 hover:bg-zijpalm-50 focus-within:bg-zijpalm-50">
                                            <input type="checkbox" name="product_ids[]" value="{{ $product->id }}" @checked(!old('selection_submitted') || in_array($product->id, old('product_ids', []))) class="mt-1 size-4 shrink-0 cursor-pointer accent-zijpalm-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-zijpalm-500">
                                            <span class="min-w-0 flex-1">
                                                <span class="block text-sm font-medium leading-5 text-zinc-900 [overflow-wrap:anywhere]">{{ $product->name }}</span>
                                                @if($product->activity_starts_at)
                                                    <span class="mt-0.5 block text-xs leading-5 text-zinc-600">{{ formatDate($product->activity_starts_at) }}</span>
                                                @endif
                                            </span>
                                        </label>
                                    @empty
                                        <p class="px-3 py-4 text-sm text-zinc-600">Geen producten beschikbaar.</p>
                                    @endforelse
                                </div>
                            </fieldset>
                        </div>
                    </section>

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
