{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
<x-page-wrapper page="Product Bekijken">
	<x-zijpalm-div color="transparent" :title="$product->name" :editable="false"/>

	@if (session('error'))
		<x-zijpalm-div color="light" :editable="false" width="min-w-min" title="Fout" text="{{ session('error') }}" error />
	@endif

	<div class="flex flex-row flex-wrap gap-4 justify-center items-stretch mt-2 w-full">
		@auth
			@if(auth()->user()->isAdmin())
				<x-zijpalm-button form="product-copy-form" type="submit" :label="$product->isActivityProduct() ? 'Kopieer activiteit' : 'Kopieer product'" variant="obvious" class="h-full" />
				<x-zijpalm-button :href="route('product.edit', $product)" label="Bewerk product" type="redirect" variant="obvious" class="h-full" />
				<x-zijpalm-button form="product-delete-form" type="submit" :label="$product->isActivityProduct() ? 'Verwijder activiteit' : 'Verwijder product'" variant="obvious" class="h-full" />
			@endif
		@endauth
	</div>

	@php
		$imageUrl = $product->imagePath ? \Illuminate\Support\Facades\Storage::disk('public')->url($product->imagePath) : null;
		$memberPriceFormatted = '€' . number_format((float) $memberPrice, 2, ',', '.');
		$guestPriceFormatted = '€' . number_format((float) $guestPrice, 2, ',', '.');
		$quantityValue = old('quantity');
		$quantityValue = is_numeric($quantityValue) ? max(1, (int) $quantityValue) : 1;
	@endphp

	<div class="flex flex-wrap flex-col lg:flex-row gap-5 flex-1 mt-3">
		<x-zijpalm-div :editable="false" width="w-full lg:w-1/2" padding="p-0" class="flex flex-1 self-stretch overflow-hidden min-h-fit md:h-[40rem]">
			<div class="flex flex-col flex-1">
				<div class="flex flex-col min-h-0 flex-1">
					@if($imageUrl)
						<img src="{{ $imageUrl }}" class="size-full object-cover flex-1" alt="{{ $product->name }}">
					@else
						<div class="size-full bg-[rgba(0,0,0,0.1)] flex items-center justify-center font-semibold">Geen afbeelding beschikbaar</div>
					@endif
				</div>

				<div class="flex flex-col justify-evenly gap-2 p-2 lg:flex-row lg:flex-wrap">
					<div class="flex flex-col lg:flex-1 self-stretch">
						<span class="text-xl font-bold">Algemeen</span>
						<div class="flex flex-col justify-evenly items-center flex-wrap bg-[rgba(0,0,0,0.1)] rounded-xl p-1 gap-y-1 flex-1">
							<div class="flex flex-col text-center">
								<span class="text-lg font-bold">Naam</span>
								<span>{{ $product->name }}</span>
							</div>
							<div class="flex flex-col text-center">
								<span class="text-lg font-bold">Type</span>
								<span>{{ $product->isActivityProduct() ? 'Activiteit' : 'Product' }}</span>
							</div>
							<div class="flex flex-col text-center w-full px-2">
								<span class="text-lg font-bold">Beschrijving</span>
								<p class="text-wrap">{{ $product->description }}</p>
							</div>
						</div>
					</div>

					<div class="flex flex-col lg:flex-1 self-stretch">
						<span class="text-xl font-bold">Prijs en voorraad</span>
						<div class="flex justify-evenly items-center flex-wrap bg-[rgba(0,0,0,0.1)] rounded-xl p-1 gap-x-2 gap-y-1 flex-1">
							<div class="flex flex-col text-center">
								<span class="text-lg font-bold">Ledenprijs</span>
								<span>{{ $memberPriceFormatted }}</span>
							</div>
							@if($product->isActivityProduct())
								<div class="flex flex-col text-center">
									<span class="text-lg font-bold">Gastprijs</span>
									<span>{{ $guestPriceFormatted }}</span>
								</div>
							@endif
							<div class="flex flex-col text-center">
								<span class="text-lg font-bold">Voorraad</span>
								<span>{{ $product->stock !== null ? $product->stock . ' stuks' : 'Onbeperkt' }}</span>
							</div>
							@if($product->pickupLocation)
								<div class="flex flex-col text-center">
									<span class="text-lg font-bold">Ophaallocatie</span>
									<span class="text-wrap">{{ $product->pickupLocation }}</span>
								</div>
							@endif
						</div>
					</div>
				</div>
			</div>
		</x-zijpalm-div>

		<x-zijpalm-div title="Bestellen" :editable="false" width="w-full lg:w-1/2" class="flex flex-col flex-1 self-stretch md:h-[40rem]">
			<form id="product-purchase-form" method="POST" action="{{ route('product.purchase', $product) }}" class="flex flex-col gap-y-3 h-full">
				@csrf

				<div class="bg-[rgba(0,0,0,0.1)] rounded-xl p-3">
					<div class="max-w-xl mx-auto">
						<label for="quantity" class="block font-semibold text-zinc-100 text-center">Aantal</label>
						<input
							id="quantity"
							name="quantity"
							type="number"
							inputmode="numeric"
							step="1"
							min="1"
							@if($product->stock !== null) max="{{ $product->stock }}" @endif
							value="{{ $quantityValue }}"
							autocomplete="off"
							class="w-full border border-[rgba(255,255,255,0.35)] rounded-xl px-3 py-2 bg-[rgba(255,255,255,0.16)] text-zinc-100 text-center text-lg font-semibold focus:outline-none focus:ring-2 focus:ring-zijpalm-200/70"
							oninput="this.value = this.value.replace(/[^0-9]/g, '');"
							required
						>
						<p class="text-sm text-zinc-100/90 mt-1 text-center">Kies hoeveel je wilt bestellen.</p>
					</div>
				</div>

				@guest
					<div class="bg-[rgba(0,0,0,0.1)] rounded-xl p-3">
						<p class="font-bold mb-2 text-zinc-100">Bestellen als gast</p>
						<div class="grid grid-cols-1 md:grid-cols-2 gap-2">
							<div>
								<label for="firstName" class="block font-semibold text-zinc-100">Voornaam</label>
								<input id="firstName" name="firstName" type="text" value="{{ old('firstName') }}" autocomplete="given-name" class="w-full border border-zinc-300 rounded px-3 py-2 bg-zinc-100 text-zinc-900" required>
							</div>
							<div>
								<label for="lastName" class="block font-semibold text-zinc-100">Achternaam</label>
								<input id="lastName" name="lastName" type="text" value="{{ old('lastName') }}" autocomplete="family-name" class="w-full border border-zinc-300 rounded px-3 py-2 bg-zinc-100 text-zinc-900" required>
							</div>
							<div>
								<label for="email" class="block font-semibold text-zinc-100">E-mail</label>
								<input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" class="w-full border border-zinc-300 rounded px-3 py-2 bg-zinc-100 text-zinc-900" required>
							</div>
							<div>
								<label for="phone" class="block font-semibold text-zinc-100">Telefoon (optioneel)</label>
								<input id="phone" name="phone" type="text" value="{{ old('phone') }}" autocomplete="tel" class="w-full border border-zinc-300 rounded px-3 py-2 bg-zinc-100 text-zinc-900">
							</div>
						</div>
					</div>
				@endguest

				<div class="mt-auto flex flex-wrap justify-center gap-3">
					<x-zijpalm-button :href="route('product.index')" label="Terug" type="redirect" variant="obvious" class="h-full" />
					<x-zijpalm-button type="submit" form="product-purchase-form" label="Ga door naar betaling" variant="obvious" />
				</div>
			</form>
		</x-zijpalm-div>
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

	<script>
		document.addEventListener('DOMContentLoaded', function () {
			const quantityInput = document.getElementById('quantity');

			if (!quantityInput) {
				return;
			}

			const min = Number(quantityInput.min || 1);
			const hasMax = quantityInput.hasAttribute('max');
			const max = hasMax ? Number(quantityInput.max) : null;

			function clampValue(rawValue) {
				let value = Number(rawValue);
				if (!Number.isFinite(value) || value < min) {
					value = min;
				}
				if (hasMax && max !== null && value > max) {
					value = max;
				}
				quantityInput.value = String(Math.trunc(value));
			}

			quantityInput.addEventListener('blur', function () {
				clampValue(quantityInput.value);
			});

			clampValue(quantityInput.value);
		});
	</script>
</x-page-wrapper>
