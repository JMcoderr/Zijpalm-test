{{-- This view file shows the activity suggestion form and follows the default Zijpalm page style. --}}
<x-page-wrapper page="Idee voor een activiteit">
    <x-zijpalm-div :editable="false" color="transparent" title="Idee voor een activiteit?" />

    <x-zijpalm-div :editable="false" color="light" width="w-full sm:w-2/3" class="text-zinc-900">
        @if(session('success'))
            <div class="mb-4 p-3 bg-green-100 border border-green-300 text-green-900 rounded-sm font-semibold">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-3 bg-red-100 border border-red-300 text-red-900 rounded-sm font-semibold">
                {{ session('error') }}
            </div>
        @endif

        <p class="font-semibold text-zinc-900">
            Vul hieronder je idee in. Velden zijn verplicht behalve bijlagen.
        </p>

        <form id="suggestion-form" method="POST" action="{{ route('activity.processSuggestion') }}" enctype="multipart/form-data" class="flex flex-col gap-y-3 mt-4">
            @csrf

            <div>
                <label for="name" class="block font-bold text-zinc-900 mb-1">Je naam</label>
                <input type="text" id="name" name="name" required value="{{ old('name') }}" placeholder="Voornaam en achternaam"
                       class="w-full h-11 px-3 rounded-sm shadow-sm bg-zinc-100 text-black placeholder:text-zinc-700 border border-zinc-300 focus:outline-0" />
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block font-bold text-zinc-900 mb-1">Je e-mailadres</label>
                <input type="email" id="email" name="email" required value="{{ old('email') }}" placeholder="Je e-mailadres"
                       class="w-full h-11 px-3 rounded-sm shadow-sm bg-zinc-100 text-black placeholder:text-zinc-700 border border-zinc-300 focus:outline-0" />
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="activity_name" class="block font-bold text-zinc-900 mb-1">Voor te stellen activiteit</label>
                <input type="text" id="activity_name" name="activity_name" required maxlength="255" value="{{ old('activity_name') }}" placeholder="Voorgestelde activeit"
                       class="w-full h-11 px-3 rounded-sm shadow-sm bg-zinc-100 text-black placeholder:text-zinc-700 border border-zinc-300 focus:outline-0" />
                @error('activity_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description" class="block font-bold text-zinc-900 mb-1">Beschrijving activiteit</label>
                <textarea id="description" name="description" required rows="10" maxlength="5000"
                          placeholder="Beschrijf hier je activiteit"
                          class="w-full px-3 py-3 rounded-sm shadow-sm bg-zinc-100 text-black placeholder:text-zinc-700 border border-zinc-300 focus:outline-0 resize-y">{{ old('description') }}</textarea>
                <p class="mt-1 text-sm text-zinc-700">Max. 5000 tekens</p>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="attachments" class="block font-bold text-zinc-900 mb-1">Bijlagen toevoegen (optioneel)</label>
                <input type="file" id="attachments" name="attachments[]" multiple accept="image/*,application/pdf,.doc,.docx"
                       class="w-full h-11 px-3 rounded-sm shadow-sm bg-zinc-100 text-black border border-zinc-300 file:mr-3 file:px-3 file:py-2 file:border-0 file:bg-zijpalm-500 file:text-white" />
                <p class="mt-1 text-sm text-zinc-700">Toegestane typen: afbeelding, PDF, DOC, DOCX. Max 10MB per bestand.</p>
                <div id="file-preview" class="mt-2 text-sm text-zinc-800"></div>
                @error('attachments')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                @error('attachments.*')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end gap-2 mt-2">
                <x-zijpalm-button type="redirect" :href="route('activity.index')" label="Annuleren" />
                <x-zijpalm-button type="submit" form="suggestion-form" label="Verzenden" />
            </div>
        </form>
    </x-zijpalm-div>

    <script>
        const fileInput = document.getElementById('attachments');
        const filePreview = document.getElementById('file-preview');

        fileInput.addEventListener('change', function () {
            if (!this.files.length) {
                filePreview.innerHTML = '';
                return;
            }

            const names = Array.from(this.files).map((file) => `${file.name} (${(file.size / 1024).toFixed(0)} KB)`);
            filePreview.innerHTML = `<strong>Geselecteerd:</strong><br>${names.join('<br>')}`;
        });
    </script>
</x-page-wrapper>
