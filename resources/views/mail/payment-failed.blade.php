{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
<x-layouts.mail.header :user="$user">
    {!! $content->textHTML !!}
    <p>Je betaling is helaas niet gelukt. Wil je nogmaals proberen je aan te melden?</p>
    <p>Mocht het na een nieuwe poging nog steeds niet lukken, neem dan contact met ons op.</p>

</x-layouts.mail.header>    