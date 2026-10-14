@php
    $isActivity = $product->isActivityProduct();
    $unitPrice = auth()->check() ? (float) $product->price : $product->displayGuestPrice();
    $maxQuantity = min(50, $availableQuantity ?? 50);
    $quantityValue = old('quantity', 1);
    $quantityValue = is_scalar($quantityValue) ? (string) $quantityValue : '1';
    $initialQuantityValid = ctype_digit($quantityValue) && (int) $quantityValue >= 1 && (int) $quantityValue <= $maxQuantity;
    $unavailableMessage = null;
    if ($product->hidden) {
        $unavailableMessage = 'Dit item is nog niet gepubliceerd.';
    } elseif ($availableQuantity === 0) {
        $unavailableMessage = $isActivity ? 'Deze activiteit zit vol. Aanmelden is niet meer mogelijk.' : 'Dit product is uitverkocht.';
    } elseif ($isActivity && $product->registration_starts_at && now()->startOfDay()->lt($product->registration_starts_at)) {
        $unavailableMessage = 'Aanmelden kan vanaf ' . $product->registration_starts_at->format('d-m-Y') . '.';
    } elseif ($isActivity && $product->registration_ends_at && now()->startOfDay()->gt($product->registration_ends_at)) {
        $unavailableMessage = 'De aanmeldperiode voor deze activiteit is gesloten.';
    } elseif (!auth()->check() && !$product->allow_guest_checkout) {
        $unavailableMessage = $isActivity ? 'Alleen leden kunnen zich aanmelden. Log eerst in.' : 'Alleen leden kunnen dit product bestellen. Log eerst in.';
    }
@endphp

<section data-shop-checkout aria-labelledby="shop-checkout-title"
         class="self-start overflow-hidden rounded-2xl bg-linear-to-t from-zijpalm-700 to-zijpalm-500 bg-zijpalm-600 inset-shadow-sm inset-shadow-zijpalm-400 text-left text-zinc-100 shadow-md">
    <header class="border-b border-white/15 bg-black/10 p-5 sm:p-6">
        <div class="flex items-center gap-4">
            <div class="grid size-12 shrink-0 place-items-center rounded-xl bg-white/15">
                <flux:icon :name="$isActivity ? 'calendar-days' : 'shopping-bag'" class="size-6"/>
            </div>
            <div>
                <h2 id="shop-checkout-title" class="text-2xl font-bold">{{ $isActivity ? 'Aanmelden' : 'Bestellen' }}</h2>
                <p class="mt-1 text-sm text-zijpalm-100">{{ $isActivity ? 'Reserveer jouw plek voor deze activiteit.' : 'Kies je aantal en bekijk het totaal.' }}</p>
            </div>
        </div>
        <ol data-checkout-steps class="mt-5 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2" aria-label="{{ $isActivity ? 'Aanmeldstappen' : 'Bestelstappen' }}">
            <li class="rounded-lg border border-white/20 bg-black/10 px-4 py-3 font-medium">
                1. {{ $isActivity ? 'Deelnemers kiezen' : 'Aantal kiezen' }}
            </li>
            <li class="rounded-lg border border-white/20 bg-black/10 px-4 py-3 font-medium">
                2. Veilig betalen
            </li>
        </ol>
    </header>

    <div class="p-5 sm:p-6">
        @if($unavailableMessage)
            <p class="rounded-xl bg-black/10 p-4 text-sm leading-relaxed text-zinc-100" role="status">{{ $unavailableMessage }}</p>
        @else
            <form id="product-purchase-form" method="POST" action="{{ route('product.purchase', $product) }}"
                  x-data="{
                      quantity: {{ \Illuminate\Support\Js::from($quantityValue) }},
                      maxQuantity: {{ $maxQuantity }},
                      unitPriceCents: {{ (int) round($unitPrice * 100) }},
                      get validQuantity() {
                          const value = Number(this.quantity);
                          return String(this.quantity).trim() !== '' &amp;&amp; Number.isInteger(value) &amp;&amp; value >= 1 &amp;&amp; value &lt;= this.maxQuantity;
                      },
                      formatTotal() {
                          return this.validQuantity ? new Intl.NumberFormat('nl-NL', { style: 'currency', currency: 'EUR' }).format(this.unitPriceCents * Number(this.quantity) / 100) : 'Kies een geldig aantal';
                      },
                      adjustQuantity(change) {
                          if (this.validQuantity) this.quantity = String(Math.min(this.maxQuantity, Math.max(1, Number(this.quantity) + change)));
                      }
                  }">
                @csrf
                <div class="mb-5 flex items-center justify-between gap-4 rounded-xl bg-black/10 p-4">
                    <div class="min-w-0">
                        <span class="text-xs text-zijpalm-100">{{ $isActivity ? 'Jouw activiteit' : 'Jouw product' }}</span>
                        <h3 class="text-lg font-bold wrap-anywhere">{{ $product->name }}</h3>
                    </div>
                    <span class="shrink-0 text-right text-xl font-bold text-zinc-100">
                        {{ formatPrice($unitPrice) }}
                        <small class="block text-xs font-normal text-zijpalm-100">{{ $isActivity ? 'per deelnemer' : 'per stuk' }}</small>
                    </span>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <label for="quantity" class="font-bold">{{ $isActivity ? 'Aantal deelnemers' : 'Aantal stuks' }}</label>
                        <p id="quantity-help" class="mt-1 text-sm text-zijpalm-100">{{ $isActivity ? 'Tel jezelf ook mee.' : 'Hoeveel wil je bestellen?' }}</p>
                    </div>
                    <div data-quantity-control class="inline-flex shrink-0 items-center overflow-hidden rounded-xl border border-zijpalm-200 bg-white text-zijpalm-700 shadow-sm">
                        <button type="button" aria-label="{{ $isActivity ? 'Een deelnemer minder' : 'Een stuk minder' }}"
                                x-on:click="adjustQuantity(-1)" x-bind:disabled="!validQuantity || Number(quantity) &lt;= 1"
                                class="flex h-12 w-12 shrink-0 cursor-pointer items-center justify-center bg-zijpalm-50 text-zijpalm-700 transition-colors hover:bg-zijpalm-100 disabled:cursor-not-allowed disabled:bg-zinc-100 disabled:text-zinc-400 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-zijpalm-500">
                            <flux:icon name="minus" variant="outline" class="h-5 w-5 shrink-0"/>
                        </button>
                        <input id="quantity" name="quantity" type="number" inputmode="numeric" step="1" min="1" max="{{ $maxQuantity }}"
                               value="{{ $quantityValue }}" x-model="quantity" x-bind:aria-invalid="!validQuantity"
                               aria-describedby="quantity-help quantity-limit quantity-error" required
                               class="h-12 w-14 rounded-none border-x border-y-0 border-zijpalm-200 [appearance:textfield] bg-white p-0 text-zijpalm-700 text-center text-lg font-bold focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-zijpalm-500 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
                        <button type="button" aria-label="{{ $isActivity ? 'Een deelnemer meer' : 'Een stuk meer' }}"
                                x-on:click="adjustQuantity(1)" x-bind:disabled="!validQuantity || Number(quantity) >= maxQuantity"
                                class="flex h-12 w-12 shrink-0 cursor-pointer items-center justify-center bg-zijpalm-50 text-zijpalm-700 transition-colors hover:bg-zijpalm-100 disabled:cursor-not-allowed disabled:bg-zinc-100 disabled:text-zinc-400 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-zijpalm-500">
                            <flux:icon name="plus" variant="outline" class="h-5 w-5 shrink-0"/>
                        </button>
                    </div>
                </div>
                <p id="quantity-limit" class="mt-3 text-xs leading-relaxed text-zijpalm-100">
                    {{ $availableQuantity === null ? 'Onbeperkt beschikbaar' : ($isActivity ? $availableQuantity . ' plaatsen beschikbaar' : $availableQuantity . ' stuks op voorraad') }}
                    &middot; Maximaal {{ $maxQuantity }} {{ $isActivity ? 'deelnemers per aanmelding' : 'stuks per bestelling' }}.
                </p>
                <p id="quantity-error" class="mt-2 rounded-lg bg-red-100 px-3 py-2 text-sm text-red-800" x-show="!validQuantity" x-cloak role="alert">
                    Vul een heel aantal in tussen 1 en {{ $maxQuantity }}.
                </p>

                @guest
                    <fieldset class="mt-5 rounded-xl bg-black/10 p-4">
                        <legend class="font-bold">Jouw gegevens</legend>
                        <p class="mt-1 text-sm leading-relaxed text-zijpalm-100">Je hoeft geen account aan te maken. We gebruiken deze gegevens voor {{ $isActivity ? 'je aanmelding' : 'je bestelling' }}.</p>
                        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            @foreach([
                                'firstName' => ['Voornaam', 'text', 'given-name', true],
                                'lastName' => ['Achternaam', 'text', 'family-name', true],
                                'email' => ['E-mailadres', 'email', 'email', true],
                                'phone' => ['Telefoon (optioneel)', 'tel', 'tel', false],
                            ] as $field => [$label, $type, $autocomplete, $required])
                                <div>
                                    <label for="{{ $field }}" class="mb-1 block text-sm font-semibold">{{ $label }}</label>
                                    <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" value="{{ old($field) }}"
                                           autocomplete="{{ $autocomplete }}" @required($required)
                                           @if($errors->has($field)) aria-invalid="true" aria-describedby="{{ $field }}-error" @endif
                                           class="min-h-11 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-zinc-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-zijpalm-500 aria-invalid:border-red-700">
                                    @error($field)
                                        <p id="{{ $field }}-error" class="mt-1 rounded-lg bg-red-100 px-2 py-1 text-sm text-red-800">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endforeach
                        </div>
                    </fieldset>
                @else
                    <div class="mt-5 flex items-start gap-3 rounded-xl bg-black/10 p-4 text-sm leading-relaxed text-zijpalm-100">
                        <flux:icon name="check-circle" class="size-5 shrink-0 text-zijpalm-100"/>
                        <p class="wrap-anywhere">Je bent ingelogd als <strong>{{ auth()->user()->name }}</strong>. Je ontvangt de bevestiging op {{ auth()->user()->email }}.</p>
                    </div>
                @endguest

                <div class="mt-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-white/25 bg-white/10 p-4">
                    <div>
                        <span class="font-bold">Totaal te betalen</span>
                        <small class="mt-1 block text-xs text-zijpalm-100">{{ auth()->check() ? 'Ledenprijs' : 'Niet-ledenprijs' }} &middot; {{ formatPrice($unitPrice) }} {{ $isActivity ? 'per deelnemer' : 'per stuk' }}</small>
                    </div>
                    <output id="checkout-total" for="quantity" aria-live="polite" aria-atomic="true" x-text="formatTotal()" class="text-right text-2xl font-extrabold text-zinc-100">{{ $initialQuantityValid ? formatPrice($unitPrice * (int) $quantityValue) : 'Kies een geldig aantal' }}</output>
                </div>
                <p class="mt-3 text-sm leading-relaxed text-zijpalm-100">{{ $isActivity ? 'Je aanmelding wordt bevestigd zodra de betaling is ontvangen.' : 'Voor het ophalen van je bestelling nemen we contact met je op.' }}</p>
                <button type="submit" x-bind:disabled="!validQuantity"
                        class="mt-4 flex min-h-13 w-full cursor-pointer items-center justify-center gap-2 rounded-full bg-linear-to-t from-zijpalm-700 to-zijpalm-500 inset-shadow-sm inset-shadow-zijpalm-300 px-4 py-3 font-bold text-zinc-100 shadow-md transition-[filter] hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-40 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-zijpalm-100">
                    <flux:icon name="lock-closed" class="size-5 shrink-0"/>
                    {{ $isActivity ? 'Aanmelden en betalen' : 'Bestellen en betalen' }}
                    <flux:icon name="arrow-right" class="size-5 shrink-0"/>
                </button>
                <p class="mt-2 text-center text-xs text-zijpalm-100">Je betaalt veilig via Mollie.</p>
            </form>
        @endif
        <a href="{{ $isActivity ? route('product.activities') : route('product.index') }}"
           class="mx-auto mt-5 flex w-fit items-center gap-2 rounded text-sm text-zijpalm-100 hover:text-white hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-zijpalm-100">
            <flux:icon name="arrow-left" class="size-4"/>
            {{ $isActivity ? 'Terug naar activiteiten' : 'Verder kijken in de shop' }}
        </a>
    </div>
</section>
