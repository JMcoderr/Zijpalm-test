{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
<x-page-wrapper page="Admin Mail Leden">
    <x-zijpalm-div :editable=false color="light">
        <x-admin.layout :heading="__('Content')" :subheading="__('Verstuur een mail naar alle Zijpalm leden.')">
            {{-- Creates a dropdown with all the content pieces for each content group --}}
            <div class="flex flex-col">
                @php
                    $batchSize = (int) config('mail.power_automate.batch_size.default');
                    $delay = (int) config('mail.power_automate.delay.default');
                    $estimatedBatches = $batchSize > 0 ? (int) ceil($recipientCount / $batchSize) : 0;
                    $estimatedSeconds = $estimatedBatches * $delay;
                @endphp
                {{-- Show errors, if any --}}
                @if($errors->any())
                    <x-zijpalm-div color="light" title="Foutmelding(en)" :editable="false" error id="error-messages"
                                   onclick="this.remove()">
                        <ul class="text-center">
                            @foreach($errors->all() as $error)
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

                <form id="notify-new-employees-form" method="POST" action="{{route('admin.notifyAllMembersPOST')}}">
                    @CSRF
                    <x-input-group grid grid="grid grid-cols-1 grid-rows-[auto_18rem] auto-rows-auto">
                        <x-input-group class="items-stretch">
                            <x-input-field id="subject" label="Onderwerp" type="text" placeholder="Vul hier het onderwerp van de e-mail in" required/>
                        </x-input-group>
                        <x-input-group class="items-stretch">
                            <x-input-field id="description" label="Beschrijving" type="editor" required/>
                        </x-input-group>
                        <x-input-group grid="grid grid-cols-1">
                            <x-input-field id="batch_size" label="Hoeveelheid ontvangers in de BCC per mail" type="number"
                                           :value="config('mail.power_automate.batch_size.default')"
                                           :min="config('mail.power_automate.batch_size.min')"
                                           :max="config('mail.power_automate.batch_size.max')" required/>
                            <x-input-field id="delay" label="Wachttijd tussen mails in seconden" type="number"
                                           :value="config('mail.power_automate.delay.default')"
                                           :min="config('mail.power_automate.delay.min')"
                                           :max="config('mail.power_automate.delay.max')" required/>
                            <div class="mt-2 rounded-lg border border-zinc-200 bg-white/90 px-3 py-2 text-sm text-zinc-700 shadow-sm">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="font-semibold text-zinc-900">Ontvangers</span>
                                    <span id="all-members-recipient-count" class="font-bold text-zijpalm-700">{{ number_format($recipientCount, 0, ',', '.') }}</span>
                                </div>
                                <div class="mt-1 flex flex-wrap items-center justify-between gap-2">
                                    <span class="font-semibold text-zinc-900">Geschatte duur</span>
                                    <span id="all-members-estimated-duration" class="font-bold text-zijpalm-700"></span>
                                </div>
                            </div>
                            <x-zijpalm-button form="notify-new-employees-form" type="submit" label="Verstuur bericht"
                                              center="horizontal" class="mt-2"/>
                        </x-input-group>
                    </x-input-group>
                </form>
            </div>
        </x-admin.layout>
    </x-zijpalm-div>
</x-page-wrapper>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const recipientCount = Number(@json($recipientCount));
        const batchSizeInput = document.getElementById('batch_size');
        const delayInput = document.getElementById('delay');
        const durationEl = document.getElementById('all-members-estimated-duration');

        const formatDuration = (seconds) => {
            const safeSeconds = Math.max(0, Math.floor(Number(seconds) || 0));
            const hours = Math.floor(safeSeconds / 3600);
            const minutes = Math.floor((safeSeconds % 3600) / 60);
            const secs = safeSeconds % 60;
            if (hours > 0) return `${hours}u ${minutes}m ${secs}s`;
            if (minutes > 0) return `${minutes}m ${secs}s`;
            return `${secs}s`;
        };

        const updateDuration = () => {
            const batchSize = Math.max(1, Number(batchSizeInput?.value || 1));
            const delay = Math.max(0, Number(delayInput?.value || 0));
            const batches = Math.ceil(recipientCount / batchSize);
            durationEl.textContent = formatDuration(batches * delay);
        };

        batchSizeInput?.addEventListener('input', updateDuration);
        delayInput?.addEventListener('input', updateDuration);
        updateDuration();
    });
</script>
