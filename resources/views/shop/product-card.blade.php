@php
    $imageUrl = $product->imagePath
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($product->imagePath)
        : \Illuminate\Support\Facades\Storage::url('/images/logo.png');
    $availableQuantity = $product->availableQuantity();
@endphp
<div class="flex flex-col max-w-[24rem] w-[90dvw] duration-400">
    <x-zijpalm-div :editable="false" color="light" width="w-full" padding="p-0" class="h-full flex flex-col overflow-hidden">
        <div class="rounded-t-xl overflow-hidden bg-white">
            <img src="{{ $imageUrl }}" loading="lazy" alt="{{ $product->name }}" class="w-full h-52 object-cover">
        </div>
        <div class="flex flex-col gap-2 p-3 flex-1">
            <span class="text-xl font-bold">{{ $product->name }}</span>
            @if($product->hidden)
                <span class="font-bold text-red-700">Verborgen - alleen zichtbaar voor beheerders</span>
            @endif
            <flux:separator/>
            <div class="flex flex-wrap justify-evenly gap-2 text-center">
                <div>
                    <span class="block font-extrabold">Ledenprijs</span>
                    <span>{{ formatPrice($product->price) }}</span>
                </div>
                <div>
                    <span class="block font-extrabold">Niet-ledenprijs</span>
                    <span>{{ formatPrice($product->displayGuestPrice()) }}</span>
                </div>
                <div>
                    <span class="block font-extrabold">{{ $product->isActivityProduct() ? 'Beschikbare plaatsen' : 'Voorraad' }}</span>
                    <span>{{ $availableQuantity === null ? 'Onbeperkt' : $availableQuantity . ($product->isActivityProduct() ? ' plaatsen' : ' stuks') }}</span>
                </div>
            </div>
            @if(!$product->allow_guest_checkout)
                <p class="text-sm text-center">Alleen leden kunnen dit item bestellen.</p>
            @endif
            <flux:separator/>
            <x-zijpalm-button :label="$product->isActivityProduct() ? 'Bekijk activiteit' : 'Bekijk product'" :href="route('product.show', $product)" center="horizontal"/>
        </div>
    </x-zijpalm-div>
</div>
