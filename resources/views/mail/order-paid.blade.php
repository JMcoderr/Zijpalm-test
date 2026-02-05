<x-layouts.mail.header :user="$user">
    {!! $content->textHTML !!}

    <h3 style="margin: 0; padding: 0;">Bestelling #{{ $order->id }}</h3>
    @foreach ($order->products as $product)
        <div style="margin-bottom: 10px;">
            <strong>{{ $product->name }}</strong><br>
            Aantal: {{ $product->pivot->quantity }}<br>
            Prijs per stuk: {{ formatPrice($product->price) }}<br>
            Totaal: {{ formatPrice($product->pivot->quantity * $product->price) }}
        </div>
    @endforeach
    <p><strong>Totaalbedrag:</strong> {{ formatPrice($order->payment->price) }}</p>
</x-layouts.mail.header>