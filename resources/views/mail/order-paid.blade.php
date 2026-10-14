{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
<x-layouts.mail.header :user="$user">
    {!! str_replace(
        ['Uw bestelling', 'uw bestelling', 'Hieronder vindt u'],
        ['Je bestelling', 'je bestelling', 'Hieronder vind je'],
        $content->textHTML
    ) !!}

    <h3 style="margin: 0; padding: 0;">Bestelling #{{ $order->id }}</h3>
    @foreach ($order->products as $product)
        <div style="margin-bottom: 10px;">
            @php
                $unitPrice = $product->pivot->unit_price ?? $product->price;
            @endphp
            <strong>{{ $product->name }}</strong><br>
            Aantal: {{ $product->pivot->quantity }}<br>
            Prijs per stuk: {{ formatPrice($unitPrice) }}<br>
            @if($order->products->count() > 1)
                Subtotaal: {{ formatPrice($product->pivot->quantity * $unitPrice) }}
            @endif
        </div>
    @endforeach
    <p><strong>Totaalbedrag:</strong> {{ formatPrice($order->payment->price) }}</p>
    @if($order->products->contains(fn ($product) => !$product->isActivityProduct()))
        <p>Voor het ophalen van je bestelling nemen we contact met je op.</p>
    @endif
</x-layouts.mail.header>