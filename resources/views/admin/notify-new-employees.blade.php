{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
<x-page-wrapper page="Admin Mail Nieuwe Medewerkers">
    <x-zijpalm-div :editable=false color="light">
        <x-admin.layout :heading="__('Content')" :subheading="__('Verstuur een mail naar alle nieuwe medewerkers van de gemeente.')">
            {{-- Creates a dropdown with all the content pieces for each content group --}}
            <div class="flex flex-col">
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

                <form id="notify-new-employees-form" method="POST" action="{{route("admin.notifyNewEmployeesPOST")}}" enctype="multipart/form-data">
                    @CSRF
                    @php
                        $batchSize = (int) old('batch_size', config('mail.power_automate.batch_size.default'));
                        $delay = (int) old('delay', config('mail.power_automate.delay.default'));
                    @endphp
                    <x-input-group grid grid="grid grid-cols-1 grid-rows-[auto_auto_18rem] auto-rows-auto">
                        <x-input-group class="items-stretch">
                            <x-input-field id="subject" label="Onderwerp" type="text" placeholder="Vul hier het onderwerp van de e-mail in" required/>
                        </x-input-group>
                        <x-input-group class="items-stretch">
                            <x-input-field id="employee_list" label="Excellijst van nieuwe medewerkers" type="file" required/>
                            <x-zijpalm-div
                                id="employee-list-preview"
                                color="light"
                                :editable="false"
                                title="Teller"
                                titleFontSize="text-base"
                                textSize="text-sm"
                                padding="p-3"
                                width="w-full"
                                margin="mt-2"
                            >
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="font-semibold text-zinc-900">Ontvangers</span>
                                    <span id="employee-list-recipient-count" class="font-bold text-zijpalm-700">-</span>
                                </div>
                                <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
                                    <span class="font-semibold text-zinc-900">Geschatte duur</span>
                                    <span id="employee-list-estimated-duration" class="font-bold text-zijpalm-700">-</span>
                                </div>
                                <p id="employee-list-preview-message" class="mt-2 text-xs text-zinc-500">Selecteer een Excel- of CSV-bestand om de teller te berekenen.</p>
                            </x-zijpalm-div>
                        </x-input-group>
                        <x-input-group class="items-stretch">
                            <x-input-field id="description" label="Beschrijving" type="editor" required/>
                        </x-input-group>
                        <x-input-group grid="grid grid-cols-1">
                            <x-input-field id="batch_size" label="Hoeveelheid ontvangers in de BCC per mail" type="number"
                                           :value="$batchSize"
                                           :min="config('mail.power_automate.batch_size.min')"
                                           :max="config('mail.power_automate.batch_size.max')" required/>
                            <x-input-field id="delay" label="Wachttijd tussen mails in seconden" type="number"
                                           :value="$delay"
                                           :min="config('mail.power_automate.delay.min')"
                                           :max="config('mail.power_automate.delay.max')" required/>
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
        const employeeListInput = document.getElementById('employee_list');
        const batchSizeInput = document.getElementById('batch_size');
        const delayInput = document.getElementById('delay');
        const recipientCountEl = document.getElementById('employee-list-recipient-count');
        const durationEl = document.getElementById('employee-list-estimated-duration');
        const messageEl = document.getElementById('employee-list-preview-message');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        let recipientCount = 0;

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
            recipientCountEl.textContent = recipientCount > 0 ? String(recipientCount) : '-';
            durationEl.textContent = recipientCount > 0 ? formatDuration(batches * delay) : '-';
        };

        const previewCount = async () => {
            const file = employeeListInput?.files?.[0];
            if (!file) {
                recipientCount = 0;
                messageEl.textContent = 'Selecteer een Excel- of CSV-bestand om de teller te berekenen.';
                updateDuration();
                return;
            }

            const formData = new FormData();
            formData.append('employee_list', file);

            messageEl.textContent = 'Teller berekenen...';

            try {
                const response = await fetch('{{ route('admin.notifyNewEmployeesPreview') }}', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: formData,
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Kon het aantal ontvangers niet berekenen.');
                }

                recipientCount = Number(data.recipient_count || 0);
                messageEl.textContent = 'Teller berekend op basis van het geselecteerde bestand.';
                updateDuration();
            } catch (error) {
                recipientCount = 0;
                recipientCountEl.textContent = '-';
                durationEl.textContent = '-';
                messageEl.textContent = error.message || 'Kon het aantal ontvangers niet berekenen.';
            }
        };

        employeeListInput?.addEventListener('change', previewCount);
        batchSizeInput?.addEventListener('input', updateDuration);
        delayInput?.addEventListener('input', updateDuration);
    });
</script>
