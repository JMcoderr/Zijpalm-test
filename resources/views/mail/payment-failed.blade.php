{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
<x-layouts.mail.header :user="$user">
    {!! $content->textHTML !!}
    <p>Je betaling is helaas niet gelukt. Wil je het nogmaals proberen via de betaallink in de bevestigingsmail?</p>
    <p>Mocht het na een nieuwe poging nog steeds niet lukken, neem dan contact met ons op via info@zijpalm.nl of bel ons.</p>

    <p>Met vriendelijke groet,<br>
    Het Bestuur</p>
</x-layouts.mail.header>    