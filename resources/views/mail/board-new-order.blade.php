{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
<x-layouts.mail.header :user="$user">
    {{-- Show the mail content text that comes from the database. --}}
    {!! $content->textHTML !!}

    {{-- Show who placed the order. --}}
    <div style="margin-bottom: 10px;">
        <h3 style="margin: 0; padding: 0;">Besteller:</h3>
        Naam: {{ $user->name }}<br>
        Email: <a href="mailto:{{$user->email}}?subject=Bestelling%20#{{$order->id}}">{{ $user->email }}</a><br>
    </div>
    <br>
    {{-- Show the order number and the products that belong to it. --}}
    <h3 style="margin: 0; padding: 0;">Bestelling #{{ $order->id }}</h3>
    <h3 style="margin: 0; padding: 0;">Product(en):</h3>
    @foreach ($order->products as $product)
        <div style="margin-bottom: 10px;">
            <strong>{{ $product->name }}</strong><br>
            Aantal: {{ $product->pivot->quantity }}<br>
            Prijs per stuk: {{ formatPrice($product->price) }}<br>
            Totaal: {{ formatPrice($product->pivot->quantity * $product->price) }}
        </div>
    @endforeach
    {{-- Show the total amount at the bottom so it is easy to check. --}}
    <p><strong>Totaalbedrag:</strong> {{ formatPrice($order->payment->price) }}</p>
</x-layouts.mail.header>