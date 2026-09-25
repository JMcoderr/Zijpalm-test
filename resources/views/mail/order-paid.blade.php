{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
<x-layouts.mail.header :user="$user">
    {!! $content->textHTML !!}

    <h3 style="margin: 0; padding: 0;">Bestelling #{{ $order->id }}</h3>
    @foreach ($order->products as $product)
        <div style="margin-bottom: 10px;">
            @php
                $unitPrice = $product->pivot->unit_price ?? $product->price;
            @endphp
            <strong>{{ $product->name }}</strong><br>
            Aantal: {{ $product->pivot->quantity }}<br>
            Prijs per stuk: {{ formatPrice($unitPrice) }}<br>
            Totaal: {{ formatPrice($product->pivot->quantity * $unitPrice) }}
        </div>
    @endforeach
    <p><strong>Totaalbedrag:</strong> {{ formatPrice($order->payment->price) }}</p>
</x-layouts.mail.header>