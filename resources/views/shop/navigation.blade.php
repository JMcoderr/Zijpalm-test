@if(session('error'))
    <x-zijpalm-div color="light" :editable="false" title="Fout" text="{{ session('error') }}" width="min-w-min" error/>
@endif
@if(session('success'))
    <x-zijpalm-div color="light" :editable="false" title="Gelukt" text="{{ session('success') }}" width="min-w-min"/>
@endif
<x-zijpalm-div title="" color="light" :editable="false" width="w-fit max-w-full" margin="mx-auto" class="shop-navigation">
    <div class="flex flex-wrap justify-center gap-3">
        <x-zijpalm-button label="Producten" :href="route('product.index')" type="redirect"/>
        <x-zijpalm-button label="Activiteiten" :href="route('product.activities')" type="redirect"/>
        @if(auth()->user()?->isAdmin())
            <x-zijpalm-button label="Toevoegen" :href="route('product.create', $type === 'activity' ? ['type' => 'activity'] : [])" type="redirect"/>
            <x-zijpalm-button :label="$type === 'activity' ? 'Deelnemers bekijken' : 'Bestellingen bekijken'" :href="$type === 'activity' ? route('admin.activities') : route('admin.orders', ['type' => $type])" type="redirect"/>
        @endif
    </div>
</x-zijpalm-div>
