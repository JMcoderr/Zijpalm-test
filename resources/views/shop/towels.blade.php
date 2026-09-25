{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
<x-page-wrapper page="Handdoeken">
    <x-zijpalm-div color="transparent" title="Handdoeken" :editable="false"/>

    <div class="w-full flex justify-center">
        <x-zijpalm-div title="" color="light" :editable="false" width="w-max" class="mx-auto">
            <div class="flex flex-wrap justify-center gap-x-4 gap-y-2">
                <x-zijpalm-button label="Door naar voordeelwinkel" :href="route('product.index')" type="redirect" />
                @auth
                    @if(auth()->user()->isAdmin())
                        <x-zijpalm-button label="Handdoek toevoegen" :href="route('product.create', ['type' => 'product'])" type="redirect" />
                    @endif
                @endauth
            </div>
        </x-zijpalm-div>
    </div>

    <div class="p-5 w-full">
        @if($products->isEmpty())
            <div class="w-full flex justify-center">
                <div class="rounded-2xl bg-linear-to-t from-zijpalm-700 to-zijpalm-500 inset-shadow-sm shadow-md text-zinc-100 overflow-hidden" style="width: min(28rem, calc(100vw - 2rem));">
                    <div class="px-5 py-2 text-center">
                        <div class="text-3xl font-bold">Nog geen handdoeken</div>
                        <div class="font-medium">Er zijn nog geen handdoeken beschikbaar.</div>
                    </div>
                </div>
            </div>
        @else
            <div class="md:min-w-min w-full flex flex-wrap justify-center gap-5 items-stretch">
                @foreach($products as $product)
                @php
                    $imageUrl = $product->imagePath ? \Illuminate\Support\Facades\Storage::disk('public')->url($product->imagePath) : \Illuminate\Support\Facades\Storage::url('/images/logo.png');
                    $memberPriceFormatted = '€' . number_format((float) $product->price, 2, ',', '.');
                @endphp

                <div class="flex flex-col sm:flex-1 aspect-5/6 max-w-[24rem] w-[90dvw] sm:max-w-6/12 sm:min-w-6/12 md:max-w-[24rem] md:min-w-[24rem] duration-400">
                    <x-zijpalm-div :editable="false" color="light" width="w-full" padding="p-0" class="h-full flex flex-col relative overflow-hidden">
                        <div class="flex flex-col rounded-t-xl border-b border-[rgba(0,0,0,0.15)] overflow-hidden justify-center flex-3/5 bg-white">
                            <img src="{{ $imageUrl }}" loading="lazy" alt="{{ $product->name }}" class="size-full object-cover">
                        </div>

                        <div class="flex flex-col pb-2 px-3 sm:flex-2/5">
                            <span class="text-xl mt-1 -mx-1.5 font-bold whitespace-nowrap overflow-hidden text-ellipsis">{{ $product->name }}</span>
                            <flux:separator class="mb-1"/>

                            <div class="flex justify-evenly sm:gap-2 overflow-hidden overflow-ellipsis text-nowrap break-words text-center">
                                <div class="flex flex-col w-1/2">
                                    <span class="font-extrabold">Ledenprijs</span>
                                    <span class="text-wrap">{{ $memberPriceFormatted }}</span>
                                </div>
                                <div class="flex flex-col w-1/2">
                                    <span class="font-extrabold">Voorraad</span>
                                    <span class="text-wrap break-words">{{ $product->stock !== null ? $product->stock . ' stuks' : 'Onbeperkt' }}</span>
                                </div>
                            </div>

                            <flux:separator class="my-2"/>
                            <x-zijpalm-button label="Bekijk Product" :href="route('product.show', $product)" center="horizontal"/>
                        </div>
                    </x-zijpalm-div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</x-page-wrapper>
