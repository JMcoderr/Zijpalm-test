<div class="flex flex-col">
    {{-- Show errors, if any --}}
    @if($errors->any())
        <x-zijpalm-div color="light" title="Foutmelding(en)" :editable="false" width="min-w-min" error id="error-messages" onclick="this.remove()">
            <ul class="text-center">
                @foreach($errors->all() as $error)
                    <li class="">{{$error}}</li>
                @endforeach
            </ul>
        </x-zijpalm-div>
        <script>
            setTimeout(function(){
                const errorDiv = document.getElementById('error-messages');
                if(errorDiv){
                    errorDiv.remove();
                }
            }, 5000);
        </script>
    @endif
    {{--  action="{{route('application.store')}}" --}}
    <form id="signup-form" class="mx-auto flex flex-col gap-y-2.5" method="POST" enctype="multipart/form-data" autocomplete="off" >
        @csrf 

        {{-- Contactgegevens --}}
        <x-input-group id="contact-info" title="Contactgegevens" grid="grid md:grid-cols-2 grid-cols-1">
            <x-input-field type="text" label="Voornaam" id="first-name" value="{{auth()->user()->firstName}}" required disabled/>
            <x-input-field type="text" label="Achternaam" id="last-name" value="{{auth()->user()->lastName}}" required disabled/>
            <x-input-field type="text" label="Telefoonnummer" id="phone" value="{{auth()->user()->phone}}" required/>
            <x-input-field type="email" label="E-mail" id="email" value="{{auth()->user()->email}}" required/>
        </x-input-group>

        {{-- Introducees --}}
        <x-input-group id="guests" title="Introducees">
            <livewire:guest-builder/>
        </x-input-group>

        {{-- Vragen --}}
        <x-input-group id="questions" title="Vragen" grid="grid md:grid-cols-2 grid-cols-1">
            @foreach($activity->questions as $question)
                <x-input-field :type="$question->type->value" :label="$question->query" :id="$question->id" :options="$question->allOptions()" required/>
            @endforeach
        </x-input-group>

        <div class="grid grid-cols-2">
            {{-- Opmerking --}}
            <x-input-group id="comment" title="Opmerking" grid="grid grid-cols-1">
                <x-input-field type="text" label="Nog Iets Te Zeggen?" id="comment"/>
            </x-input-group>

            {{-- Te Betalen --}}
            <x-input-group id="payment" title="Te Betalen" grid="grid grid-cols-3">
                <x-input-field type="text" label="Basis" id="base-cost" value="{{$activity->price}}" readonly/>
                <x-input-field type="text" label="Opties" id="options-cost" readonly/>
                <x-input-field type="text" label="Totaal" id="total-cost" readonly/>
            </x-input-group>
        </div>
    </form>
</div>
<x-zijpalm-button form="signup-form" type="submit" label="Aanmelden" variant="obvious" center="horizontal"/>

<script>
    document.addEventListener(
        'DOMContentLoaded', 
        function(){
            const base = parseFloat(document.getElementById('base-cost').value);
            const optionsCostField = document.getElementById('options-cost');
            const totalCostField = document.getElementById('total-cost');

            const priceFields = document.querySelectorAll('input[id*="price"], select[id*="price"]');

            function calculatePrices(){
                let optionsTotal = 0;

                priceFields.forEach(field=>{
                    if(field.type==='checkbox' && field.checked){
                        optionsTotal += +field.value;
                    }
                    else if(field.type==='number'){
                        optionsTotal += +field.value;
                    }
                    else if(field.tagName==='SELECT'){
                        optionsTotal += +field.options[field.selectedIndex].value;
                    }
                });

                optionsCostField.value = optionsTotal.toFixed(2);
                totalCostField.value = (base + optionsTotal).toFixed(2);
            }

            priceFields.forEach(field=>{
                field.addEventListener('input', calculatePrices);
                field.addEventListener('change', calculatePrices);
            });

            calculatePrices();
        }
    );
</script>