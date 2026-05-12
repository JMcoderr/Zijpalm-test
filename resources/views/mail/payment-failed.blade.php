<x-layouts.mail.header :user="$user">
    {!! $content->textHTML !!}

    <p>Je betaling is helaas niet gelukt. Probeer het via de betaallink opnieuw en mocht dat niet lukken neem dan contact met ons op.</p>
</x-layouts.mail.header>    