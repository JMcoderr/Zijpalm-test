{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
@vite('resources/js/forms/display-uploaded-file-name.js')

<x-page-wrapper page="Product Bewerken">
	<x-zijpalm-div color="transparent" :title="'Bewerk product: ' . $product->name" :editable="false" class="max-w-full! [&>p]:wrap-anywhere"/>

	@if($errors->any())
		<x-zijpalm-div color="light" title="Foutmelding(en)" :editable="false" width="min-w-min" error id="error-messages" onclick="this.remove()">
			<ul class="text-center">
				@foreach($errors->all() as $error)
					<li>{{ $error }}</li>
				@endforeach
			</ul>
		</x-zijpalm-div>
	@endif

	<div class="flex flex-col">
		<x-zijpalm-div color="light" :editable="false" width="w-full" form>
			<div class="mb-3 px-3 py-2 rounded-lg border border-red-200 bg-white/90 text-sm font-medium text-zinc-800 shadow-sm w-max max-w-full mx-auto">
				<span class="text-red-500 font-black text-base align-middle">*</span>
				<span class="align-middle">Verplichte velden</span>
			</div>

			@php
				$currentImageUrl = $product->imagePath ? \Illuminate\Support\Facades\Storage::disk('public')->url($product->imagePath) : null;
				$productTypeOptions = [
					['id' => 'product', 'option' => 'Product'],
					['id' => 'activity', 'option' => 'Activiteit (apart van producten)'],
				];
			@endphp

			<form id="product-update-form" class="mx-auto p-3 flex flex-col gap-y-2.5" enctype="multipart/form-data" autocomplete="off" method="POST" action="{{ route('product.update', $product) }}">
				@csrf
				@method('PUT')

				<x-input-group id="general" title="Algemeen" grid="grid md:grid-cols-2 grid-cols-1">
					<x-input-group grid padding="p-0" grid="grid lg:grid-cols-2 md:grid-cols-1 grid-cols-2" height="h-min">
						<x-input-field type="text" label="Naam" id="name" :value="old('name', $product->name)" required/>
						<x-input-field type="file" label="Nieuwe afbeelding" id="imagePath" action="displayUploadedFileName(this)" accept="image/*"/>
						<x-input-field type="select" label="Type" id="product_type" :options="$productTypeOptions" optionValuePair :selected="old('product_type', $product->product_type)" required/>
						<x-input-field type="text" label="Ledenprijs" id="price" :value="old('price', $product->price)" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,2})?" required/>
						<x-input-field type="text" label="Niet-ledenprijs" id="guest_price" :value="old('guest_price', $product->guest_price)" inputmode="decimal" pattern="[0-9]+([.,][0-9]{1,2})?"/>
						<p class="text-sm px-2">Gebruik een punt of komma, bijvoorbeeld 15,50. Een lege niet-ledenprijs gebruikt de ledenprijs.</p>
						<x-input-field type="number" label="Voorraad" id="stock" :value="old('stock', $product->stock)"/>
						<x-input-field type="text" label="Locatie / ophaalpunt" id="pickupLocation" :value="old('pickupLocation', $product->pickupLocation)"/>
					</x-input-group>

					<x-input-group class="items-stretch [&_[data-editor-holder]]:h-auto [&_[data-editor-holder]]:min-h-48" height="h-auto" title="Beschrijving en afbeelding">
						<div data-image-preview="imagePath" data-initial-url="{{ $currentImageUrl ?? '' }}" class="rounded-lg border border-zinc-300 bg-white overflow-hidden mb-2" @if(!$currentImageUrl) hidden @endif>
							<img @if($currentImageUrl) src="{{ $currentImageUrl }}" @endif alt="{{ $product->name }}" class="max-h-96 w-full object-contain">
						</div>
						<x-input-field label="Beschrijving" id="description" type="editor" height="h-auto" :value="$product->descriptionForEditor()" required/>
					</x-input-group>
				</x-input-group>

				<x-input-group id="activity-fields" title="Activiteitsgegevens" grid="grid grid-cols-1">
					<div class="grid lg:grid-cols-2 grid-cols-1 gap-x-2 relative">
						<x-input-group id="activity-details" title="Algemeen" height="h-max" grid="grid grid-cols-2">
							<x-input-field type="number" label="Max. deelnemers" id="max_participants" :value="old('max_participants', $product->max_participants)"/>
							<div class="col-span-2 text-sm text-zinc-700 px-2">
								Niet-ledenprijs en locatie stel je hierboven in bij <span class="font-bold">Algemeen</span>. Max. deelnemers bepaalt het aantal beschikbare plaatsen. Laat voorraad leeg als alleen het deelnemersmaximum geldt.
							</div>
						</x-input-group>

						<flux:separator class="my-2 lg:hidden" variant="subtle"/>
						<flux:separator class="absolute h-full left-1/2 right-1/2 my-2 hidden lg:block" variant="subtle" vertical/>

						<x-input-group id="activity-times" title="Wanneer" height="h-max" grid="grid grid-cols-2">
							<x-input-field type="date" label="Start aanmeldperiode" id="registration_starts_at" :value="old('registration_starts_at', optional($product->registration_starts_at)->format('Y-m-d'))"/>
							<x-input-field type="date" label="Einde aanmeldperiode" id="registration_ends_at" :value="old('registration_ends_at', optional($product->registration_ends_at)->format('Y-m-d'))"/>
							<x-input-field type="date" label="Startdatum" id="activity_starts_at" :value="old('activity_starts_at', optional($product->activity_starts_at)->format('Y-m-d'))"/>
							<x-input-field type="date" label="Einddatum" id="activity_ends_at" :value="old('activity_ends_at', optional($product->activity_ends_at)->format('Y-m-d'))"/>
						</x-input-group>
					</div>
				</x-input-group>

				<x-input-group id="publication" title="Publicatie" grid="grid grid-cols-1">
					<input type="hidden" name="allow_guest_checkout" value="0">
					<input type="hidden" name="hidden" value="0">
					<x-input-field type="checkbox" label="Niet-leden mogen ook bestellen" id="allow_guest_checkout" value="1" :checked="(bool) old('allow_guest_checkout', $product->allow_guest_checkout)"/>
					<x-input-field type="checkbox" label="Verbergen voor bezoekers" id="hidden" value="1" :checked="(bool) old('hidden', $product->hidden)"/>
					<p class="text-sm px-2">Een verborgen item is alleen zichtbaar voor beheerders en kan niet worden besteld. Je kunt het later publiceren door dit vinkje uit te zetten.</p>
				</x-input-group>
			</form>
		</x-zijpalm-div>
	</div>

	<div class="mt-3 flex flex-wrap justify-center gap-3">
		<x-zijpalm-button :href="route('product.show', $product)" label="Terug" type="redirect" variant="obvious" class="h-full"/>
		<x-zijpalm-button type="submit" label="Opslaan" form="product-update-form" variant="obvious" class="h-full"/>
		<x-zijpalm-button type="submit" form="product-delete-form" label="Verwijderen" variant="obvious" class="h-full"/>
	</div>

	<form id="product-delete-form" method="POST" action="{{ route('product.destroy', $product) }}" onsubmit="return confirm('Weet je zeker dat je dit item wilt verwijderen?')">
		@csrf
		@method('DELETE')
	</form>

	<script>
		(function () {
			const typeField = document.getElementById('product_type');
			const activityFields = document.getElementById('activity-fields');

			function toggleActivityFields() {
				if (!typeField) {
					return;
				}

				const isActivity = typeField.value === 'activity';
				activityFields?.classList.toggle('hidden', !isActivity);
				activityFields?.querySelectorAll('input, select').forEach(field => field.disabled = !isActivity);
			}

			typeField?.addEventListener('change', toggleActivityFields);
			toggleActivityFields();
		})();
	</script>

</x-page-wrapper>
