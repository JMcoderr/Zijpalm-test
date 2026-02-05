<x-layouts.mail.header :user="$user">

    {!! $content->textHTML !!}

    <p>Het te betalen bedrag bedraagt: <strong>{{ formatPrice($totalCost) }}</strong></p>
    <p>Betaal het bedrag voor {{formatTime(now()->addWeekdays(2)->endOfDay())}} {{formatDate(now()->addWeekdays(2)->endOfDay())}}.</p>

    <x-mail.button :href="$paymentLink" label="Betalen" />

</x-layouts.mail.header>