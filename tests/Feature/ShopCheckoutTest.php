<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('confirms free items without contacting Mollie and reserves their capacity', function (string $type) {
    \Illuminate\Support\Facades\Mail::fake();
    config(['mail.bestuur.address' => 'board@example.com']);
    foreach (['email-bestelling-betaald', 'email-bestuur-nieuwe-bestelling'] as $name) {
        \App\Models\Content::create(['name' => $name, 'type' => 'email', 'title' => 'Bevestiging', 'text' => '']);
    }
    \Mollie\Laravel\Facades\Mollie::shouldReceive('api')->never();
    $this->actingAs(User::factory()->create());
    $product = Product::factory()->create([
        'product_type' => $type, 'price' => 0, 'hidden' => false, 'stock' => 3,
    ]);

    $this->get(route('product.show', $product))->assertOk()
        ->assertSee('2. Bevestigen')->assertDontSee('Je betaalt veilig via Mollie.')
        ->assertDontSee('Maximaal 3 deelnemers per aanmelding');
    $this->post(route('product.purchase', $product), ['quantity' => 2])
        ->assertRedirect(route('product.show', $product))->assertSessionHas('success');

    $order = \App\Models\Order::webshop()->sole();
    expect($order->payment->status)->toBe(\App\PaymentStatus::paid)
        ->and((float) $order->payment->price)->toBe(0.0)
        ->and($order->products->sole()->pivot->quantity)->toBe(2)
        ->and($product->fresh()->stock)->toBe(1);
    \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\OrderPaid::class, fn ($mail) => $mail->hasTo('board@example.com'));
    \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\BoardNewOrder::class);
})->with(['product', 'activity']);

it('shows a compact checkout with the correct product or activity wording and price', function (bool $activity, bool $guest) {
    $product = Product::factory()->create([
        'name' => $activity ? 'Dagje uit' : 'Badhanddoek',
        'product_type' => $activity ? 'activity' : 'product',
        'hidden' => false, 'allow_guest_checkout' => true,
        'price' => 15.5, 'guest_price' => 20.75, 'stock' => 10,
    ]);
    if (!$guest) {
        $this->actingAs(User::factory()->create());
    }

    $response = $this->get(route('product.show', $product))->assertOk()
        ->assertSee('data-shop-checkout', false)
        ->assertSee('Totaal te betalen')
        ->assertSee($activity ? 'Aantal deelnemers' : 'Aantal stuks')
        ->assertSee($activity ? 'Aanmelden en betalen' : 'Bestellen en betalen')
        ->assertSee('unitPriceCents: ' . ($guest ? 2075 : 1550), false)
        ->assertSee('aria-live="polite"', false)
        ->assertSee($guest ? 'Jouw gegevens' : 'Je bent ingelogd als')
        ->assertSee('data-checkout-steps', false)
        ->assertSee('data-quantity-control', false)
        ->assertDontSee('md:h-[40rem]', false);
    preg_match('/<section data-shop-checkout.*?<\/section>/s', $response->getContent(), $match);
    expect($match)->not->toBeEmpty();
    if ($activity) {
        expect($match[0])->not->toContain('Bestellen')->not->toContain('bestelling')
            ->toContain('Terug naar activiteiten')
            ->toContain('Tel jezelf ook mee.');
    } else {
        expect($match[0])->toContain('Voor het ophalen van je bestelling nemen we contact met je op.');
    }
})->with([[false, false], [false, true], [true, false], [true, true]]);

it('shows why checkout is unavailable rather than offering an unusable action', function (array $overrides, string $message) {
    $product = Product::factory()->create(array_merge([
        'product_type' => 'activity', 'hidden' => false, 'allow_guest_checkout' => true, 'stock' => 10,
    ], $overrides));
    $this->get(route('product.show', $product))->assertOk()
        ->assertSee($message)->assertDontSee('id="product-purchase-form"', false)
        ->assertSee('Terug naar activiteiten');
})->with([
    'full' => [['stock' => 0], 'Deze activiteit zit vol.'],
    'not started' => [['registration_starts_at' => now()->addDay()], 'Aanmelden kan vanaf'],
    'closed' => [['registration_ends_at' => now()->subDay()], 'De aanmeldperiode voor deze activiteit is gesloten.'],
    'members only' => [['allow_guest_checkout' => false], 'Alleen leden kunnen zich aanmelden. Log eerst in.'],
]);

it('retains entered guest details and shows field-level errors after a failed submission', function () {
    $product = Product::factory()->create([
        'hidden' => false, 'allow_guest_checkout' => true, 'stock' => 5,
    ]);
    $this->from(route('product.show', $product))->post(route('product.purchase', $product), [
        'quantity' => 2, 'firstName' => 'Test', 'lastName' => 'Gast', 'email' => 'not-an-email',
    ])->assertSessionHasErrors('email');
    $this->get(route('product.show', $product))->assertOk()
        ->assertSee('value="Test"', false)
        ->assertSee('value="not-an-email"', false)
        ->assertSee('id="email-error"', false)
        ->assertSee('quantity: \'2\'', false);
});

it('renders separate numbered steps and accessible quantity buttons without changing the form limits', function () {
    $product = Product::factory()->create([
        'hidden' => false, 'allow_guest_checkout' => true, 'stock' => 3,
        'product_type' => 'activity',
    ]);
    $response = $this->get(route('product.show', $product))->assertOk()
        ->assertSee('aria-label="Aanmeldstappen"', false)
        ->assertSee('aria-label="Een deelnemer minder"', false)
        ->assertSee('aria-label="Een deelnemer meer"', false)
        ->assertSee('step="1" min="1" max="3"', false)
        ->assertSee('Number(quantity) &lt;= 1', false)
        ->assertSee('Number(quantity) >= maxQuantity', false)
        ->assertSee('disabled:text-zinc-400', false)
        ->assertSee('h-12 w-12 shrink-0', false)
        ->assertSee('border-x border-y-0 border-zijpalm-200', false);
    preg_match('/<ol data-checkout-steps.*?<\/ol>/s', $response->getContent(), $steps);
    expect($steps)->not->toBeEmpty();
    expect(substr_count($steps[0], '<li '))->toBe(2);
    expect($steps[0])->toContain('1. Deelnemers kiezen')->toContain('2. Veilig betalen')
        ->toContain('grid-cols-1 gap-3')->toContain('sm:grid-cols-2')
        ->not->toContain('data-step-number')->not->toContain('rounded-full');
});

it('versions built styles and scripts so new uploads do not reuse stale editor code', function () {
    $expected = asset('build/css.css') . '?v=' . hash_file('sha256', public_path('build/css.css'));
    expect(\Illuminate\Support\Facades\Vite::asset('resources/css/app.css'))->toBe($expected);
    foreach (['app' => 'resources/js/app.js', 'editorjs' => 'resources/js/editor/editorjs.js', 'displayUploadedFileName' => 'resources/js/forms/display-uploaded-file-name.js'] as $bundle => $entry) {
        expect(\Illuminate\Support\Facades\Vite::asset($entry))
            ->toBe(asset('build/' . $bundle . '.js') . '?v=' . hash_file('sha256', public_path('build/' . $bundle . '.js')));
    }
});

it('preserves invalid quantities for correction instead of silently changing them to another amount', function () {
    $this->actingAs(User::factory()->create());
    $product = Product::factory()->create(['hidden' => false, 'stock' => 5]);
    $this->from(route('product.show', $product))->post(route('product.purchase', $product), [
        'quantity' => 0,
    ])->assertSessionHasErrors('quantity');
    $this->get(route('product.show', $product))->assertOk()
        ->assertSee('value="0"', false)
        ->assertSee('Kies een geldig aantal')
        ->assertSee('Controleer je bestelling');
});

it('places product information before checkout in matching top-aligned desktop columns', function () {
    $product = Product::factory()->create([
        'hidden' => false, 'allow_guest_checkout' => true, 'stock' => 5,
        'product_type' => 'activity', 'activity_starts_at' => now()->addDays(5),
    ]);
    $response = $this->get(route('product.show', $product))->assertOk()
        ->assertSeeInOrder(['data-shop-details', 'data-shop-checkout'])
        ->assertSee('lg:grid-cols-2', false)
        ->assertSee('Over deze activiteit')
        ->assertSee('Wanneer')
        ->assertSee(now()->addDays(5)->format('d-m-Y'));
    expect(substr_count($response->getContent(),
        'self-start overflow-hidden rounded-2xl bg-linear-to-t from-zijpalm-700 to-zijpalm-500 bg-zijpalm-600'))->toBe(2);
    foreach (['details', 'checkout'] as $panel) {
        preg_match('/<section data-shop-' . $panel . '[^>]*>/s', $response->getContent(), $section);
        expect($section)->not->toBeEmpty();
        expect($section[0])->toContain('text-zinc-100')->not->toContain('bg-zinc-');
    }
});
