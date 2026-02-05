@php
// Calculate the price for the remaining months of the year
$priceTillEndOfYear = (12 - date('n')) * $monthlyPrice;

// Format the price to 2 decimal places
$price = number_format($priceTillEndOfYear, 2, ',', '.');
@endphp

<x-page-wrapper page="Aanmelden">
    <x-zijpalm-div :editable=false :title="$content->title" color="transparent" />
        <x-zijpalm-div :id="$content->id" color="light" :editables="['Titel', 'Tekst']" :name="$content->name" :text="$content->textHTML">
            <form action="{{route('information.joinForm')}}" method="POST" class="flex flex-col items-center gap-y-2.5 mt-5">
                @csrf
                @method('POST')
                <x-input-group grid="grid grid-cols-2 grid-rows-2" id="textfields">
                    <x-input-field id="firstname" name="firstname" type="text" label="Voornaam" required autocomplete="given-name" />
                    <x-input-field id="lastname" name="lastname" type="text" label="Achternaam" required autocomplete="family-name" />
                    <x-input-field id="email" name="email" type="email" label="E-mail" required autocomplete="email" />
                    <x-input-field id="phone" name="phone" type="tel" label="Telefoonnummer" required autocomplete="tel" pattern="\d{1,10}$" />
                </x-input-group>
                <div name="radio" class="flex flex-col">
                    {{-- Hidden by default --}}
                    <x-input-field type="radio" id="type" name="type" label="Kies wat van toepassing is:" required action="changeEndDateVisibility()" :options="['inhuur' => 'Ik ben inhuur', 'gepensioneerde' => 'Ik ben pensionado', 'stagiair' => 'Ik ben stagiair']" />
                    <x-input-field id="endDate" name="endDate" type="date" label="Einddatum stage" min="{{now()->format('Y-m-d')}}" hidden action="calculatePrice()" />
                </div>
                <x-input-group flex="flex flex-col items-start">
                    <div class="flex">
                        <x-input-field id="privacy" name="privacy" type="checkbox" height="h-min" width="w-min" required/>
                        <span class="my-auto text-nowrap">Ik ga akkoord met de <a class="underline" target="_blank" href={{$privacy->file}}>privacyverklaring</a></span>
                    </div>
                    <div class="flex">
                        <x-input-field id="rules" name="rules" type="checkbox" height="h-min" width="w-min" required/>
                        <span class="my-auto text-nowrap">Ik ga akkoord met het <a class="underline" target="_blank" href={{$terms->file}}>huishoudelijk reglement</a></span>
                    </div>
                </x-input-group>
                <p class="h5">Contributie t/m <span id="month">december</span>: €<span id=priceString>{{$price}}</span></p>
                @if ($errors->any())
                    <div class="text-red-500">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <button type="submit" class="bg-zijpalm-400 hover:bg-zijpalm-500 text-white font-bold py-2 px-4 rounded cursor-pointer">Aanmelden</button>
            </form>
        </x-zijpalm-div>
</x-page-wrapper>
<script>
function changeEndDateVisibility() {
    const stagiairRadio = document.getElementById('stagiair');
    const endDateContainer = document.getElementById('endDate-wrapper');
    if (stagiairRadio.checked) {
        // Show the end date container
        endDateContainer.style.display = 'flex';
        // Set endDate input to required
        endDateContainer.querySelector('input').required = true;
    } else {
        // Hide the end date container
        endDateContainer.style.display = 'none';
        // Set endDate input to not required
        endDateContainer.querySelector('input').required = false;
    }
    // Call calculatePrice to update the price if the end date is hidden
    calculatePrice();
}
</script>
<script>
function calculatePrice() {
    const monthlyPrice = 2.00;
    const endDate = document.getElementById('endDate');
    const priceString = document.getElementById('priceString');
    const month = document.getElementById('month');
    const stagiair = document.getElementById('stagiair');

    // If endDate is filled in, calculate the price based on the difference in months until the endDate
    if (endDate.value !== '' && stagiair.checked) {
        const endDateValue = new Date(endDate.value);
        const currentDate = new Date();

        // Calculate difference in total months
        const yearDiff = endDateValue.getFullYear() - currentDate.getFullYear();
        const monthDiff = endDateValue.getMonth() - currentDate.getMonth();

        // Total months difference
        let totalMonths = yearDiff * 12 + monthDiff;

        // If endDate day is earlier than current day, reduce one month
        if (endDateValue.getDate() < currentDate.getDate()) {
            totalMonths -= 1;
        }

        const price = monthlyPrice * totalMonths;
        priceString.innerHTML = price.toFixed(2).replace('.', ',');
        month.innerHTML = endDateValue.toLocaleString('nl', { month: 'long' });
    } else { // Else, set the price based on the remaining months until the end of the year
        // Get the price from the php variable
        priceString.innerHTML = '{{$price}}';
        month.innerHTML = 'december';
    }
}
</script>
