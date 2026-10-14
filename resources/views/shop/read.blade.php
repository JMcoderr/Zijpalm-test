{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
<x-page-wrapper :page="$product->isActivityProduct() ? 'Activiteit bekijken' : 'Product bekijken'">
	<x-zijpalm-div color="transparent" :title="$product->name" :editable="false"/>

	@if (session('error'))
		<x-zijpalm-div color="light" :editable="false" width="min-w-min" title="Fout" text="{{ session('error') }}" error />
	@endif
	@if($errors->any())
		<x-zijpalm-div color="light" :editable="false" :title="$product->isActivityProduct() ? 'Controleer je aanmelding' : 'Controleer je bestelling'" width="w-full" error>
			<ul role="alert">
				@foreach($errors->all() as $error)
					<li>{{ $error }}</li>
				@endforeach
			</ul>
		</x-zijpalm-div>
	@endif
	@if($product->hidden)
		<p class="text-center font-bold">Dit item is verborgen voor bezoekers en is nog niet beschikbaar.</p>
	@endif

	<div class="flex flex-row flex-wrap gap-4 justify-center items-stretch mt-2 w-full">
		@auth
			@if(auth()->user()->isAdmin())
				<x-zijpalm-button form="product-copy-form" type="submit" :label="$product->isActivityProduct() ? 'Kopieer activiteit' : 'Kopieer product'" variant="obvious" class="h-full" />
				<x-zijpalm-button :href="route('product.edit', $product)" :label="$product->isActivityProduct() ? 'Bewerk activiteit' : 'Bewerk product'" type="redirect" variant="obvious" class="h-full" />
				<x-zijpalm-button form="product-delete-form" type="submit" :label="$product->isActivityProduct() ? 'Verwijder activiteit' : 'Verwijder product'" variant="obvious" class="h-full" />
			@endif
		@endauth
	</div>

	@php
		$availableQuantity = $product->availableQuantity();
	@endphp

	<div data-shop-layout class="grid w-full grid-cols-1 items-start gap-5 mt-4 lg:grid-cols-2">
		@include('shop.details', ['product' => $product, 'availableQuantity' => $availableQuantity])
		@include('shop.checkout', ['product' => $product, 'availableQuantity' => $availableQuantity])
	</div>

	@auth
		@if(auth()->user()->isAdmin())
			<form id="product-copy-form" method="POST" action="{{ route('product.copy', $product) }}" onsubmit="return confirm('Je staat op het punt dit item te kopiëren. Doorgaan?')">
				@csrf
			</form>

			<form id="product-delete-form" method="POST" action="{{ route('product.destroy', $product) }}" onsubmit="return confirm('Weet je zeker dat je dit item wilt verwijderen?')">
				@csrf
				@method('DELETE')
			</form>
		@endif
	@endauth

</x-page-wrapper>
