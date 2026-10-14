<?php

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mollie\Api\MollieApiClient;
use Mollie\Laravel\Facades\Mollie;

uses(RefreshDatabase::class);

function webshopProductData(array $overrides = []): array
{
    return array_merge([
        'name' => 'Badhanddoek Zijpalm',
        'description' => 'Een product uit de webwinkel',
        'product_type' => 'product',
        'price' => '15,50',
        'guest_price' => '17,75',
        'stock' => 10,
        'hidden' => 0,
        'allow_guest_checkout' => 1,
    ], $overrides);
}

it('publishes multiple products without the old towel-name filter and keeps activities separate', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    foreach (['Badhanddoek Zijpalm', 'Zijpalm tas'] as $name) {
        $this->post(route('product.store'), webshopProductData(['name' => $name]))
            ->assertRedirect(route('product.index'))->assertSessionHas('success');
    }
    $this->post(route('product.store'), webshopProductData([
        'name' => 'Dagje uit', 'product_type' => 'activity',
        'max_participants' => 20, 'stock' => null,
    ]))->assertRedirect(route('product.activities'));

    $this->get(route('product.index'))->assertOk()
        ->assertSee('Badhanddoek Zijpalm')->assertSee('Zijpalm tas')->assertDontSee('Dagje uit')
        ->assertSee('Shop/Overig')->assertDontSee('SHOP/OVERIG')
        ->assertSee('Toevoegen')->assertSee('Niet-ledenprijs');
    $this->get(route('product.activities'))->assertOk()->assertSee('Dagje uit')
        ->assertDontSee('Badhanddoek Zijpalm')->assertSee('Beschikbare plaatsen');
    $this->get(route('product.towels'))->assertRedirect(route('product.index'));

    expect(Product::where('name', 'Zijpalm tas')->firstOrFail()->price)->toBe('15.50');
});

it('uses a compact centered navigation bar on both shop lists', function () {
    foreach ([route('product.index'), route('product.activities')] as $url) {
        $response = $this->get($url)->assertOk();
        expect($response->getContent())->toMatch('/class="[^"]*mx-auto w-fit max-w-full[^"]*shop-navigation/');
    }
});

it('preserves the Shop/Overig capitalization in desktop and mobile navigation', function () {
    $response = $this->get(route('product.index'))->assertOk();
    expect($response->getContent())
        ->toContain('<span style="text-transform: none;">Shop/Overig</span>')
        ->toContain('<p style="text-transform: none;">Shop/Overig</p>')
        ->not->toContain('SHOP/OVERIG');
});

it('accepts decimal prices with a comma or dot when creating and editing', function (string $price) {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->post(route('product.store'), webshopProductData([
        'price' => $price, 'guest_price' => $price,
    ]))->assertSessionHasNoErrors()->assertRedirect(route('product.index'));
    $product = Product::where('name', 'Badhanddoek Zijpalm')->firstOrFail();
    expect($product->price)->toBe('15.50')->and($product->guest_price)->toBe('15.50');

    $this->put(route('product.update', $product), webshopProductData([
        'price' => $price, 'guest_price' => '',
    ]))->assertSessionHasNoErrors()->assertRedirect(route('product.show', $product));
    expect($product->fresh()->price)->toBe('15.50')
        ->and($product->fresh()->guest_price)->toBeNull()
        ->and($product->fresh()->displayGuestPrice())->toBe(15.5);
})->with(['15,50', '15.50', '15,5', '15.5']);

it('accepts whole euro prices without decimal input', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('product.store'), webshopProductData(['price' => '15', 'guest_price' => '']))
        ->assertSessionHasNoErrors()->assertRedirect(route('product.index'));
    $product = Product::where('name', 'Badhanddoek Zijpalm')->firstOrFail();
    expect($product->price)->toBe('15.00')->and($product->displayGuestPrice())->toBe(15.0);
});

it('rejects invalid prices instead of silently rounding or changing them', function (string $price) {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('product.store'), webshopProductData(['price' => $price]))
        ->assertSessionHasErrors('price');
    expect(Product::count())->toBe(0);
})->with(['15,500', '-5', 'not-a-price', '1,000.50', '']);

it('ignores hidden activity fields for products and clears them when changing type', function () {
    $this->actingAs(User::factory()->admin()->create())->post(route('product.store'),
        webshopProductData([
            'max_participants' => 'bad', 'registration_starts_at' => 'invalid',
            'registration_ends_at' => 'invalid',
        ]))->assertSessionHasNoErrors();
    $product = Product::where('name', 'Badhanddoek Zijpalm')->firstOrFail();
    expect($product->max_participants)->toBeNull()->and($product->registration_starts_at)->toBeNull();
    $product->update([
        'product_type' => 'activity', 'max_participants' => 3,
        'registration_starts_at' => now()->subDay(), 'activity_starts_at' => now()->addDay(),
    ]);
    $this->put(route('product.update', $product), webshopProductData())->assertSessionHasNoErrors();
    expect($product->fresh()->product_type)->toBe('product')
        ->and($product->fresh()->max_participants)->toBeNull()
        ->and($product->fresh()->activity_starts_at)->toBeNull();
});

it('shows hidden items only to administrators and allows publishing a copied item', function () {
    $product = Product::factory()->create(['name' => 'Verborgen tas', 'hidden' => true, 'product_type' => 'product']);
    $this->get(route('product.index'))->assertOk()->assertDontSee('Verborgen tas');
    $this->get(route('product.show', $product))->assertNotFound();

    $this->actingAs(User::factory()->admin()->create());
    $this->get(route('product.index'))->assertOk()->assertSee('Verborgen tas')
        ->assertSee('alleen zichtbaar voor beheerders');
    $this->post(route('product.copy', $product))->assertRedirect();
    $copy = Product::where('name', 'Verborgen tas (kopie)')->firstOrFail();
    expect($copy->hidden)->toBeTrue();
    $this->put(route('product.update', $copy), webshopProductData([
        'name' => $copy->name, 'hidden' => 0,
    ]))->assertSessionHasNoErrors();
    $this->post(route('logout'));
    $this->get(route('product.index'))->assertOk()->assertSee('Verborgen tas (kopie)');
});

it('renders decimal-friendly fields and clear visibility explanations on both forms', function () {
    $this->actingAs(User::factory()->admin()->create());
    $product = Product::factory()->create();
    foreach ([route('product.create'), route('product.edit', $product)] as $url) {
        $this->get($url)->assertOk()->assertSee('inputmode="decimal"', false)
            ->assertSee('Verbergen voor bezoekers')
            ->assertSee('Een verborgen item is alleen zichtbaar voor beheerders')
            ->assertSee('Activiteit (apart van producten)');
    }
});

it('shows both prices publicly and only permits guest checkout when enabled', function () {
    Mollie::shouldReceive('api')->never();
    $product = Product::factory()->create([
        'price' => 15.5, 'guest_price' => 20.75, 'hidden' => false, 'allow_guest_checkout' => false,
    ]);
    $this->get(route('product.index'))->assertOk()->assertSee('Niet-ledenprijs')
        ->assertSee(formatPrice(20.75));
    $this->get(route('product.show', $product))->assertOk()->assertSee('Niet-ledenprijs')
        ->assertSee(formatPrice(20.75))->assertDontSee('id="product-purchase-form"', false);
    $this->post(route('product.purchase', $product), [
        'quantity' => 1, 'firstName' => 'Test', 'lastName' => 'Gast', 'email' => 'test@example.com',
    ])->assertSessionHas('error');
    expect(Order::count())->toBe(0);
});

it('charges the correct price and reserves stock for member and guest checkout', function (bool $guest) {
    $product = Product::factory()->create([
        'price' => 15.5, 'guest_price' => 20.75, 'stock' => 5,
        'hidden' => false, 'allow_guest_checkout' => true, 'product_type' => 'product',
    ]);
    $expectedPrice = $guest ? '41.50' : '31.00';
    $endpoint = Mockery::mock();
    $endpoint->shouldReceive('create')->once()->with(Mockery::on(
        fn ($data) => $data['amount']['value'] === $expectedPrice
    ))->andReturn((object) ['id' => 'tr_shop_test']);
    $endpoint->shouldReceive('get')->once()->with('tr_shop_test')->andReturn((object) [
        '_links' => (object) ['checkout' => (object) ['href' => 'https://example.com/pay']],
    ]);
    $client = Mockery::mock(MollieApiClient::class);
    $client->payments = $endpoint;
    Mollie::shouldReceive('api')->twice()->andReturn($client);

    if (!$guest) {
        $this->actingAs(User::factory()->create());
    }
    $this->post(route('product.purchase', $product), [
        'quantity' => 2, 'firstName' => 'Test', 'lastName' => 'Gast', 'email' => 'test@example.com',
    ])->assertStatus(303)->assertRedirect('https://example.com/pay');

    $order = Order::firstOrFail();
    expect($order->is_guest_checkout)->toBe($guest)
        ->and($order->payment->price)->toBe($expectedPrice)
        ->and((float) $order->products->first()->pivot->unit_price)->toBe($guest ? 20.75 : 15.5)
        ->and($product->fresh()->stock)->toBe(3);
})->with([false, true]);

it('calculates remaining activity places using paid and awaiting orders rather than the maximum', function () {
    $product = Product::factory()->create([
        'product_type' => 'activity', 'stock' => null, 'max_participants' => 10, 'hidden' => false,
    ]);
    foreach ([PaymentStatus::paid, PaymentStatus::pending, PaymentStatus::expired] as $status) {
        $payment = Payment::create([
            'mollieId' => 'tr_capacity_' . $status->value, 'status' => $status,
            'description' => 'Bestelling #test', 'price' => 30,
        ]);
        $order = Order::create(['user_id' => User::factory()->create()->id, 'payment_id' => $payment->id]);
        $order->products()->attach($product, ['quantity' => 2, 'unit_price' => 15]);
    }
    expect($product->availableQuantity())->toBe(6);
    $this->get(route('product.activities'))->assertOk()->assertSee('6 plaatsen')
        ->assertDontSee('10 plekken');
    $this->get(route('product.show', $product))->assertOk()->assertSee('6 plaatsen');
});

it('blocks invalid quantities, insufficient stock and out-of-period activity purchases', function () {
    Mollie::shouldReceive('api')->never();
    $this->actingAs(User::factory()->create());
    $product = Product::factory()->create(['stock' => 1, 'hidden' => false]);
    $this->post(route('product.purchase', $product), ['quantity' => 0])->assertSessionHasErrors('quantity');
    $this->post(route('product.purchase', $product), ['quantity' => 2])->assertSessionHas('error');
    $product->update(['product_type' => 'activity', 'registration_starts_at' => now()->addDay()]);
    $this->post(route('product.purchase', $product), ['quantity' => 1])->assertSessionHas('error');
    $product->update(['registration_starts_at' => now()->subDays(2), 'registration_ends_at' => now()->subDay()]);
    $this->post(route('product.purchase', $product), ['quantity' => 1])->assertSessionHas('error');
    expect(Order::count())->toBe(0)->and($product->fresh()->stock)->toBe(1);
});

it('blocks an activity purchase that exceeds remaining places before contacting Mollie', function () {
    Mollie::shouldReceive('api')->never();
    $this->actingAs(User::factory()->create());
    $product = Product::factory()->create([
        'product_type' => 'activity', 'stock' => null, 'hidden' => false, 'max_participants' => 1,
    ]);
    $this->post(route('product.purchase', $product), ['quantity' => 2])
        ->assertSessionHas('error', 'Er zijn niet genoeg plekken meer beschikbaar voor deze activiteit.');
    expect(Order::count())->toBe(0);
});
