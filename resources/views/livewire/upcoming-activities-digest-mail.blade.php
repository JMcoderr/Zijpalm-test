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

        {{-- Show success message, if any --}}
        @if(!empty($successMessage))
            <x-zijpalm-div color="light" :editable="false" :text="$successMessage" success id="success-messages"
                           onclick="this.remove()">
            </x-zijpalm-div>
            <script>
                setTimeout(function () {
                    const successDiv = document.getElementById('success-messages');
                    if (successDiv) {
                        successDiv.remove();
                    }
                }, 5000);
            </script>
        @endif

        <p class="mb-4 text-gray-900">
            Met deze actie verstuurt u een mail met alle toekomstige activiteiten naar alle actieve leden.<br>
            Dit kan enige tijd duren afhankelijk van het aantal leden en de wachttijd tussen mails.
        </p>

        <form wire:submit="sendDigest" class="flex flex-col gap-4">
            <x-zijpalm-button type="submit" label="Mail toekomstige activiteiten versturen" center="horizontal"/>
        </form>
    </div>
</div>
