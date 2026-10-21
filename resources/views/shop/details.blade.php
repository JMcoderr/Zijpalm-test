@php
    $isActivity = $product->isActivityProduct();
    $imageUrl = $product->imagePath ? \Illuminate\Support\Facades\Storage::disk('public')->url($product->imagePath) : null;
@endphp
<section data-shop-details aria-labelledby="shop-details-title"
         class="self-start overflow-hidden rounded-2xl bg-linear-to-t from-zijpalm-700 to-zijpalm-500 bg-zijpalm-600 inset-shadow-sm inset-shadow-zijpalm-400 text-left text-zinc-100 shadow-md">
    <header class="flex items-center gap-4 border-b border-white/15 bg-black/10 p-5 sm:p-6">
        <div class="grid size-12 shrink-0 place-items-center rounded-xl bg-white/15">
            <flux:icon :name="$isActivity ? 'calendar-days' : 'shopping-bag'" class="size-6"/>
        </div>
        <div>
            <h2 id="shop-details-title" class="text-2xl font-bold">{{ $isActivity ? 'Over deze activiteit' : 'Over dit product' }}</h2>
            <p class="mt-1 text-sm text-zijpalm-100">{{ $product->name }}</p>
        </div>
    </header>
    <div class="space-y-5 p-5 sm:p-6">
        @if($imageUrl)
            <a href="{{ $imageUrl }}" target="_blank" rel="noopener" aria-label="{{ $product->name }}: volledige afbeelding bekijken">
                <img src="{{ $imageUrl }}" alt="{{ $product->name }}" class="h-auto max-h-[36rem] w-full rounded-lg bg-white/10 object-contain">
            </a>
        @else
            <div class="flex aspect-video flex-col items-center justify-center gap-3 rounded-xl bg-black/10 text-zijpalm-100">
                <flux:icon name="photo" class="size-10"/>
                <span class="text-sm">Geen afbeelding beschikbaar</span>
            </div>
        @endif
        <div class="rounded-xl bg-black/10 p-4">
            <h3 class="mb-2 text-base font-bold">{{ $isActivity ? 'Wat kun je verwachten?' : 'Beschrijving' }}</h3>
            <x-shop-description :product="$product" />
        </div>
        <dl class="grid grid-cols-2 gap-3">
            <div class="rounded-xl bg-black/10 p-4">
                <dt class="text-xs text-zijpalm-100">Ledenprijs</dt>
                <dd class="mt-1 text-xl font-bold">{{ formatPrice($product->price) }}</dd>
            </div>
            <div class="rounded-xl bg-black/10 p-4">
                <dt class="text-xs text-zijpalm-100">Niet-ledenprijs</dt>
                <dd class="mt-1 text-xl font-bold">{{ formatPrice($product->displayGuestPrice()) }}</dd>
            </div>
            <div class="col-span-2 flex items-center gap-3 rounded-xl bg-black/10 p-4">
                <flux:icon :name="$isActivity ? 'users' : 'cube'" class="size-5 shrink-0 text-zijpalm-100"/>
                <div>
                    <dt class="text-xs text-zijpalm-100">{{ $isActivity ? 'Beschikbare plaatsen' : 'Voorraad' }}</dt>
                    <dd class="mt-1 text-sm font-semibold">{{ $availableQuantity === null ? 'Onbeperkt' : $availableQuantity . ($isActivity ? ' plaatsen' : ' stuks') }}</dd>
                </div>
            </div>
            @if($product->pickupLocation)
                <div class="col-span-2 flex items-start gap-3 rounded-xl bg-black/10 p-4">
                    <flux:icon name="map-pin" class="size-5 shrink-0 text-zijpalm-100"/>
                    <div>
                        <dt class="text-xs text-zijpalm-100">{{ $isActivity ? 'Locatie' : 'Ophaallocatie' }}</dt>
                        <dd class="mt-1 text-sm font-semibold wrap-anywhere">{{ $product->pickupLocation }}</dd>
                    </div>
                </div>
            @endif
            @if($isActivity && $product->activity_starts_at)
                <div class="col-span-2 flex items-start gap-3 rounded-xl bg-black/10 p-4">
                    <flux:icon name="calendar-days" class="size-5 shrink-0 text-zijpalm-100"/>
                    <div>
                        <dt class="text-xs text-zijpalm-100">Wanneer</dt>
                        <dd class="mt-1 text-sm font-semibold">
                            {{ $product->activity_starts_at->format('d-m-Y') }}
                            @if($product->activity_ends_at && !$product->activity_starts_at->isSameDay($product->activity_ends_at))
                                t/m {{ $product->activity_ends_at->format('d-m-Y') }}
                            @endif
                        </dd>
                    </div>
                </div>
            @endif
            @if($isActivity && $product->registration_ends_at)
                <div class="col-span-2 rounded-xl bg-black/10 p-4">
                    <dt class="text-xs text-zijpalm-100">Aanmelden kan tot en met</dt>
                    <dd class="mt-1 text-sm font-semibold">{{ $product->registration_ends_at->format('d-m-Y') }}</dd>
                </div>
            @endif
        </dl>
    </div>
</section>
