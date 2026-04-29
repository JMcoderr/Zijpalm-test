<x-layouts.mail.header :user="$user">
    {!! $content->textHTML !!}

    @if($refundedAmount > 0)
        <p>Het volgende bedrag is teruggestort op jouw rekening, dit kan enkele werkdagen duren:
        <strong>{{ formatPrice($refundedAmount) }}</strong></p>
    @endif
</x-layouts.mail.header>