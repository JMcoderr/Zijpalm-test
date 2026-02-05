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

        <p class="mb-4 text-gray-900">
            Met deze actie verstuurt u een aankondiging over de huidige activiteit.<br>
            Alle leden van de Personeelsvereniging die zich niet voor deze activiteit hebben aangemeld ontvangen deze boodschap.
        </p>

        <form id="announcement-mail-form" method="POST" action="{{route('activity.notifyMembers', $activity)}}">
            @CSRF
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
                    <x-zijpalm-button form="announcement-mail-form" type="submit" label="Kondig activiteit aan"
                                      center="horizontal" class="mt-2"/>
                </x-input-group>
            </x-input-group>
        </form>
    </div>
</div>
