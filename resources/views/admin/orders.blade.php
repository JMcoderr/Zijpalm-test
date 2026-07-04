{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
<x-page-wrapper page="Bestellingen bekijken">
    <x-zijpalm-div color="transparent" title="Bestellingen bekijken" :editable="false"/>

    @php
        $allOrders = $memberOrders->merge($guestOrders);
        $orderCount = $allOrders->count();
        $memberOrderCount = $memberOrders->count();
        $guestOrderCount = $guestOrders->count();

        $statusClassFor = function ($statusText) {
            return match ($statusText) {
                'paid' => 'bg-emerald-100 text-emerald-800',
                'pending', 'open', 'authorized' => 'bg-amber-100 text-amber-800',
                'failed', 'expired', 'canceled' => 'bg-red-100 text-red-800',
                default => 'bg-zinc-100 text-zinc-700',
            };
        };
    @endphp

    <x-zijpalm-div color="light" :editable="false" width="w-full">
        <x-admin.layout :heading="__('Bestellingen')" :subheading="__('Overzicht van webshop bestellingen')">
            <x-zijpalm-div title="Overzicht" color="light" :editable="false" width="w-full" class="mb-2">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="rounded-xl bg-white/90 p-3 text-center">
                        <p class="text-sm font-semibold text-zinc-600">Totaal</p>
                        <p class="text-3xl font-black text-zijpalm-700">{{ $orderCount }}</p>
                    </div>
                    <div class="rounded-xl bg-white/90 p-3 text-center">
                        <p class="text-sm font-semibold text-zinc-600">Leden</p>
                        <p class="text-3xl font-black text-zijpalm-700">{{ $memberOrderCount }}</p>
                    </div>
                    <div class="rounded-xl bg-white/90 p-3 text-center">
                        <p class="text-sm font-semibold text-zinc-600">Niet-leden</p>
                        <p class="text-3xl font-black text-zijpalm-700">{{ $guestOrderCount }}</p>
                    </div>
                </div>
            </x-zijpalm-div>

            @php
                $sections = [
                    [
                        'title' => 'Ledenbestellingen',
                        'orders' => $memberOrders,
                        'typeBadge' => 'Lid',
                        'typeBadgeClass' => 'bg-blue-100 text-blue-800',
                        'counts' => $memberStatusCounts,
                        'emptyText' => 'Nog geen bestellingen van ingelogde leden.',
                    ],
                    [
                        'title' => 'Gastbestellingen',
                        'orders' => $guestOrders,
                        'typeBadge' => 'Gast',
                        'typeBadgeClass' => 'bg-purple-100 text-purple-800',
                        'counts' => $guestStatusCounts,
                        'emptyText' => 'Nog geen bestellingen via gast-checkout.',
                    ],
                ];
            @endphp

            @if($orderCount === 0)
                <x-zijpalm-div title="Nog geen webshop bestellingen" color="light" :editable="false" width="w-full">
                    <div class="rounded-xl bg-white/90 border border-zinc-200 p-4 text-center">
                        <p class="text-xl font-black text-zijpalm-700">Nog geen bestellingen</p>
                        <p class="text-sm text-zinc-700 mt-1">Nieuwe shopbestellingen verschijnen hier automatisch zodra iemand afrekent.</p>
                    </div>
                </x-zijpalm-div>
            @else
                @foreach($sections as $section)
                    <x-zijpalm-div :title="$section['title']" color="light" :editable="false" width="w-full">
                        <div class="flex flex-wrap items-center gap-2 mb-3 text-xs font-semibold">
                            <span class="rounded-full px-2.5 py-1 bg-emerald-100 text-emerald-800">Betaald: {{ $section['counts']['paid'] }}</span>
                            <span class="rounded-full px-2.5 py-1 bg-amber-100 text-amber-800">In afwachting: {{ $section['counts']['pending'] }}</span>
                            <span class="rounded-full px-2.5 py-1 bg-red-100 text-red-800">Mislukt/geannuleerd: {{ $section['counts']['failed'] }}</span>
                        </div>

                        @if($section['orders']->isEmpty())
                            <div class="rounded-xl bg-white/90 border border-zinc-200 p-3 text-sm text-zinc-700">
                                {{ $section['emptyText'] }}
                            </div>
                        @else
                            <div class="flex flex-col gap-3">
                                @foreach($section['orders'] as $order)
                                    @php
                                        $totalPrice = $order->products->sum(function ($product) {
                                            $unitPrice = $product->pivot->unit_price ?? $product->price;
                                            return $unitPrice * $product->pivot->quantity;
                                        });

                                        $productSummary = $order->products
                                            ->map(fn ($product) => $product->name . ' x' . $product->pivot->quantity)
                                            ->implode(', ');

                                        $statusText = $order->payment?->status?->value ?? 'onbekend';
                                        $statusClass = $statusClassFor($statusText);
                                        $orderedAt = $order->payment?->created_at?->format('d-m-Y H:i') ?? '-';
                                    @endphp

                                    <div class="rounded-xl bg-white/95 p-3 border border-zinc-200">
                                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="text-xl font-black text-zijpalm-700">Bestelling #{{ $order->id }}</span>
                                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $section['typeBadgeClass'] }}">{{ $section['typeBadge'] }}</span>
                                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $statusClass }}">{{ $statusText }}</span>
                                                </div>
                                                <p class="text-sm opacity-90">{{ $order->user?->email ?? 'Geen e-mail bekend' }}</p>
                                                <p class="text-sm opacity-90">Besteld op: {{ $orderedAt }}</p>
                                                <p class="text-sm opacity-80 mt-1">{{ $productSummary ?: '-' }}</p>
                                            </div>

                                            <div class="flex items-center gap-2 shrink-0">
                                                <span class="font-bold">{{ formatPrice($totalPrice) }}</span>
                                                <x-zijpalm-button :href="route('admin.orders.show', $order)" label="Openen" type="redirect" />
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </x-zijpalm-div>
                @endforeach
            @endif
        </x-admin.layout>
    </x-zijpalm-div>
</x-page-wrapper>
