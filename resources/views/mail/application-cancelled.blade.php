<x-layouts.mail.header :user="$user">
    <p>Uw afmelding voor {{$activity->title}} is succesvol</p>

    {!! $content->textHTML !!}

    Het volgende bedrag is teruggestort op jouw rekening, dit kan enkele werkdagen duren:
    <strong>{{ formatPrice($refundedAmount) }}</strong>

</x-layouts.mail.header>
