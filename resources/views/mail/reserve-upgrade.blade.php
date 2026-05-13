{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
<x-layouts.mail.header :user="$user">


    <p>Joepie, er is een plekje vrijgekomen dus je kan alsnog meedoen met deze activiteit, al dan niet met intro.</p>

    <p>Het te betalen bedrag bedraagt €{{ formatPrice($totalCost) }} en je hebt 2 dagen om het te betalen anders gaat je plek naar de volgende op de reserve lijst.</p>

    <x-mail.button :href="$paymentLink" label="Betalen" />

</x-layouts.mail.header>