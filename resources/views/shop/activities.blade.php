<x-page-wrapper page="Shop/Overig - Activiteiten">
    <x-zijpalm-div color="transparent" title="Webwinkel Zijpalm - Activiteiten" :editable="false"/>
    @include('shop.navigation', ['type' => 'activity'])

    <div class="p-5 w-full">
        <div class="w-full flex flex-wrap justify-center gap-5 items-stretch">
            @forelse($products as $product)
                @include('shop.product-card', ['product' => $product])
            @empty
                <x-zijpalm-div :editable="false" width="w-full" title="Nog geen activiteiten" text="Er staan nog geen activiteiten in de webwinkel."/>
            @endforelse
        </div>
    </div>
</x-page-wrapper>
