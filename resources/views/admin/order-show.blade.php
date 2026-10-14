{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
<x-page-wrapper page="Bestelling details">
    <x-zijpalm-div color="transparent" :title="'Bestelling #' . $order->id" :editable="false"/>

    @php
        $totalPrice = $order->products->sum(function ($product) {
            $unitPrice = $product->pivot->unit_price ?? $product->price;
            return $unitPrice * $product->pivot->quantity;
        });

        $statusText = $order->payment?->status?->value ?? 'onbekend';
        $statusClass = match ($statusText) {
            'paid' => 'bg-emerald-100 text-emerald-800',
            'pending', 'open', 'authorized' => 'bg-amber-100 text-amber-800',
            'failed', 'expired', 'canceled' => 'bg-red-100 text-red-800',
            default => 'bg-zinc-100 text-zinc-700',
        };
    @endphp

    <x-zijpalm-div color="light" :editable="false" width="w-full">
        <x-admin.layout :heading="__('Bestelling #' . $order->id)" :subheading="__('Details en acties')">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                <x-zijpalm-button :href="route('admin.orders')" label="Terug naar bestellingen" type="redirect" />
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">Status: {{ $statusText }}</span>
                    @if($order->isAwaitingPayment())
                        <form method="POST" action="{{ route('admin.orders.destroy', $order) }}" onsubmit="return confirm('Weet je zeker dat je deze openstaande bestelling wilt annuleren en verwijderen?')">
                            @csrf
                            @method('DELETE')
                            <flux:tooltip content="Bestelling verwijderen">
                                <flux:button type="submit" variant="danger" icon="trash" square="true" aria-label="Bestelling verwijderen" class="cursor-pointer"/>
                            </flux:tooltip>
                        </form>
                    @endif
                </div>
            </div>

            <x-zijpalm-div title="Samenvatting" color="light" :editable="false" width="w-full">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="rounded-xl bg-white/90 p-3 text-center">
                        <p class="text-sm font-semibold text-zinc-600">Bestelling</p>
                        <p class="text-3xl font-black text-zijpalm-700">#{{ $order->id }}</p>
                    </div>
                    <div class="rounded-xl bg-white/90 p-3 text-center">
                        <p class="text-sm font-semibold text-zinc-600">Aantal producten</p>
                        <p class="text-3xl font-black text-zijpalm-700">{{ $order->products->count() }}</p>
                    </div>
                    <div class="rounded-xl bg-white/90 p-3 text-center">
                        <p class="text-sm font-semibold text-zinc-600">Totaal</p>
                        <p class="text-3xl font-black text-zijpalm-700">{{ formatPrice($totalPrice) }}</p>
                    </div>
                </div>
            </x-zijpalm-div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <x-zijpalm-div title="Besteller" color="light" :editable="false" width="w-full">
                    <div class="rounded-xl bg-white/90 p-3 text-sm space-y-1">
                        <p><span class="font-bold">Naam:</span> {{ $order->user?->name ?? '-' }}</p>
                        <p><span class="font-bold">E-mail:</span> {{ $order->user?->email ?? '-' }}</p>
                        <p><span class="font-bold">Telefoon:</span> {{ formatPhoneNumber($order->user?->phone) }}</p>
                    </div>
                </x-zijpalm-div>

                <x-zijpalm-div title="Betaling" color="light" :editable="false" width="w-full">
                    <div class="rounded-xl bg-white/90 p-3 text-sm space-y-1">
                        <p><span class="font-bold">Status:</span> {{ $order->payment?->status?->value ?? '-' }}</p>
                        <p><span class="font-bold">Bedrag:</span> {{ formatPrice($order->payment?->price ?? $totalPrice) }}</p>
                        <p><span class="font-bold">Mollie ID:</span> {{ $order->payment?->mollieId ?? '-' }}</p>
                    </div>
                </x-zijpalm-div>
            </div>

            <x-zijpalm-div title="Producten" color="light" :editable="false" width="w-full">
                <div class="flex flex-col gap-3">
                    @foreach($order->products as $product)
                        @php
                            $unitPrice = $product->pivot->unit_price ?? $product->price;
                        @endphp
                        <div class="rounded-xl bg-white/95 p-3 border border-zinc-200">
                            <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                                <div>
                                    <p class="text-lg font-black text-zijpalm-700">{{ $product->name }}</p>
                                    <p class="text-sm">Aantal: {{ $product->pivot->quantity }} | Prijs p/st: {{ formatPrice($unitPrice) }}</p>
                                    <p class="text-sm font-bold">Subtotaal: {{ formatPrice($unitPrice * $product->pivot->quantity) }}</p>
                                </div>
                                <div class="flex gap-2">
                                    <x-zijpalm-button :href="route('product.show', $product)" label="Bekijken" type="redirect" />
                                    <x-zijpalm-button :href="route('product.edit', $product)" label="Bewerken" type="redirect" />
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-zijpalm-div>

            <div class="mt-2 flex justify-center">
                <x-zijpalm-button :href="route('admin.orders')" label="Terug naar bestellingen" type="redirect" />
            </div>
        </x-admin.layout>
    </x-zijpalm-div>
</x-page-wrapper>
