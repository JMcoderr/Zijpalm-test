@use('App\PaymentStatus')

<div x-data="{ open: true }" x-init="$nextTick(() => open = true)" class="z-50">
    <!-- Modal Background -->
    <div x-show="open" x-transition class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <!-- Modal Box -->
        <div @click.away="open = false" class="bg-white w-full max-w-md p-6 rounded-lg shadow-xl text-center">
            <p class="mb-4 text-2xl">
                @switch(session('payment_status'))
                    @case(PaymentStatus::paid->value)
                        <span class="text-green-500">
                            ✅ De betaling is gelukt!
                        </span>
                        @break

                    @case(PaymentStatus::expired->value)
                        <span class="text-red-500">
                            ⌛ De betaling is verlopen, probeer het opnieuw
                        </span>
                        @break

                    @case(PaymentStatus::failed->value)
                        <span class="text-red-500">
                            ❌ De betaling is gefaald, probeer het opnieuw
                        </span>
                        @break

                    @case(PaymentStatus::canceled->value)
                        <span class="text-red-500">
                            🚫 De betaling was gecancelled door de gebruiker
                        </span>
                        @break

                    @default
                        <span class="text-zijpalm-500">
                            ℹ️ De betaling is nog niet verwerkt
                        </span>
                @endswitch
            </p>
            <button @click="open = false" class="bg-zijpalm-600 text-white px-4 py-2 rounded hover:bg-zijpalm-700">
                Sluit
            </button>
        </div>
    </div>
</div>