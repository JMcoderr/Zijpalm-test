{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
@vite('resources/js/forms/display-uploaded-file-name.js')

<x-page-wrapper page="Product Bewerken">
	<x-zijpalm-div color="transparent" :title="'Bewerk product: ' . $product->name" :editable="false"/>

	@if($errors->any())
		<x-zijpalm-div color="light" title="Foutmelding(en)" :editable="false" width="min-w-min" error id="error-messages" onclick="this.remove()">
			<ul class="text-center">
				@foreach($errors->all() as $error)
					<li>{{ $error }}</li>
				@endforeach
			</ul>
		</x-zijpalm-div>
		<script>
			setTimeout(function () {
				const errorDiv = document.getElementById('error-messages');
				if (errorDiv) {
					errorDiv.remove();
				}
			}, 5000);
		</script>
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
					['id' => 'product', 'option' => 'product'],
					['id' => 'activity', 'option' => 'activiteit (voordeelwinkel)'],
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
						<x-input-field type="number" label="Ledenprijs" id="price" :value="old('price', $product->price)" step="0.01" required/>
						<x-input-field type="number" label="Gastprijs" id="guest_price" :value="old('guest_price', $product->guest_price)" step="0.01"/>
						<x-input-field type="number" label="Voorraad" id="stock" :value="old('stock', $product->stock)"/>
						<x-input-field type="text" label="Locatie / ophaalpunt" id="pickupLocation" :value="old('pickupLocation', $product->pickupLocation)"/>
					</x-input-group>

					<x-input-group class="items-stretch" title="Beschrijving en afbeelding">
						@if($currentImageUrl)
							<div class="rounded-lg border border-zinc-300 bg-white overflow-hidden mb-2">
								<img src="{{ $currentImageUrl }}" alt="{{ $product->name }}" class="h-52 w-full object-cover" loading="lazy">
							</div>
						@endif
						<label for="description" class="font-bold">Beschrijving <span class="text-red-500">*</span></label>
						<textarea id="description" name="description" class="w-full min-h-44 rounded-sm shadow-sm px-2 py-1 focus:outline-0 bg-zinc-100 text-black" rows="8" required>{{ old('description', $product->description) }}</textarea>
					</x-input-group>
				</x-input-group>

				<x-input-group id="activity-fields" title="Activiteit toevoegen" grid="grid grid-cols-1">
					<div class="grid lg:grid-cols-2 grid-cols-1 gap-x-2 relative">
						<x-input-group id="activity-details" title="Algemeen" height="h-max" grid="grid grid-cols-2">
							<x-input-field type="number" label="Max. deelnemers" id="max_participants" :value="old('max_participants', $product->max_participants)"/>
							<div class="col-span-2 text-sm text-zinc-700 px-2">
								Gastprijs, voorraad en locatie stel je hierboven in bij <span class="font-bold">Algemeen</span>.
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
					<x-input-field type="checkbox" label="Verborgen item" id="hidden" value="1" :checked="(bool) old('hidden', $product->hidden)"/>
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
		document.addEventListener('DOMContentLoaded', function () {
			const typeField = document.getElementById('product_type');
			const activityFields = document.getElementById('activity-fields');
			const guestCheckoutWrapper = document.getElementById('allow_guest_checkout-wrapper');

			function toggleActivityFields() {
				if (!typeField) {
					return;
				}

				const isActivity = typeField.value === 'activity';
				activityFields?.classList.toggle('hidden', !isActivity);
				guestCheckoutWrapper?.classList.toggle('hidden', isActivity);
			}

			typeField?.addEventListener('change', toggleActivityFields);
			toggleActivityFields();
		});
	</script>

</x-page-wrapper>
