<x-page-wrapper page="Admin Mail Leden">
    <x-zijpalm-div :editable=false color="light">
        <x-admin.layout :heading="__('Content')" :subheading="__('Verstuur een mail naar alle Zijpalm leden.')">
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
                            <x-zijpalm-button form="notify-new-employees-form" type="submit" label="Verstuur bericht"
                                              center="horizontal" class="mt-2"/>
                        </x-input-group>
                    </x-input-group>
                </form>
            </div>
        </x-admin.layout>
    </x-zijpalm-div>
</x-page-wrapper>
