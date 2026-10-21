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

it('renders formatted shop descriptions and retains plain text in the editor', function () {
    $product = Product::factory()->create(['hidden' => false, 'description' => 'Bestaande beschrijving']);
    expect(json_decode($product->descriptionForEditor(), true)['blocks'][0]['data']['text'])
        ->toBe('Bestaande beschrijving');
    $this->actingAs(User::factory()->admin()->create());
    $this->get(route('product.edit', $product))->assertOk()
        ->assertSee('data-editor-holder="description-editor"', false)->assertSee('Bestaande beschrijving');
    $product->update(['description' => json_encode(['blocks' => [
        ['type' => 'paragraph', 'data' => ['text' => '<b>Spelletjes</b> <a href="https://example.com">Meer informatie</a><script>alert(1)</script>']],
    ]])]);
    $this->get(route('product.show', $product))->assertOk()
        ->assertSee('<b>Spelletjes</b>', false)->assertSee('href="https://example.com"', false)
        ->assertDontSee('<script>alert(1)</script>', false);
});

it('renders entity-encoded editor descriptions like normal activities', function () {
    $encodedDescription = htmlspecialchars(json_encode([
        'time' => 1792475372086,
        'version' => '2.31.4',
        'blocks' => [['type' => 'paragraph', 'data' => ['text' => 'Spelletjesmiddag avond<br />Volgende week spelen we samen.']]],
    ]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $product = Product::factory()->create([
        'hidden' => false,
        'product_type' => 'activity',
        'description' => $encodedDescription,
    ]);

    $this->get(route('product.show', $product))->assertOk()
        ->assertSee('Spelletjesmiddag avond')->assertSee('Volgende week spelen we samen.')
        ->assertSee('avond<br', false)
        ->assertDontSee('&quot;time&quot;', false);

    expect(json_decode($product->descriptionForEditor(), true)['blocks'][0]['data']['text'])
        ->toBe('Spelletjesmiddag avond<br />Volgende week spelen we samen.');
});

it('renders plain text line breaks explicitly for compact shop descriptions', function () {
    $description = "Eerste regel\nDirect eronder\n\nVolgende alinea";
    $product = new Product(['description' => $description]);

    expect($product->descriptionHtml())->toBe('<p>' . nl2br(e($description)) . '</p>');
});

it('saves separate editor paragraphs as line breaks in shop descriptions', function () {
    $description = htmlspecialchars(json_encode([
        'time' => 1792475372086,
        'version' => '2.31.4',
        'blocks' => [
            ['type' => 'paragraph', 'data' => ['text' => 'Eerste regel']],
            ['type' => 'paragraph', 'data' => ['text' => 'Tweede regel']],
        ],
    ]), ENT_QUOTES | ENT_HTML5, 'UTF-8');

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('product.store'), webshopProductData(['description' => $description]))
        ->assertSessionHasNoErrors();

    $product = Product::where('name', 'Badhanddoek Zijpalm')->sole();
    expect(json_decode($product->description, true)['blocks'])->toHaveCount(2);

    $this->get(route('product.show', $product))->assertOk()
        ->assertSee('data-shop-description', false)->assertSee('whitespace-normal', false)
        ->assertSee('Eerste regel</p>', false)
        ->assertSee('Tweede regel</p>', false);
});

it('preserves stored editor breaks for HTML whitespace rendering', function () {
    $product = new Product(['description' => json_encode(['blocks' => [
        ['type' => 'paragraph', 'data' => [
            'text' => "<a href=\"https://example.com\">Eerste regel<br></a>\nDirect eronder<br>\r\n<br />\nVolgende alinea",
        ]],
    ]])]);
    $document = new DOMDocument();
    $document->loadHTML($product->descriptionHtml());
    $paragraph = $document->getElementsByTagName('p')->item(0);

    expect(str_replace(["\r", "\n"], '', $paragraph->textContent))->toBe('Eerste regelDirect eronderVolgende alinea')
        ->and($paragraph->getElementsByTagName('br')->length)->toBe(3)
        ->and($paragraph->getElementsByTagName('a')->item(0)->getAttribute('href'))->toBe('https://example.com');
});

it('preserves soft breaks and empty paragraphs without rendering scripts', function () {
    $product = Product::factory()->create(['description' => json_encode([
        'time' => 1792475372086,
        'version' => '2.31.4',
        'blocks' => [
            ['type' => 'paragraph', 'data' => ['text' => 'Eerste regel<br><br />Volgende alinea']],
            ['type' => 'paragraph', 'data' => ['text' => '']],
            ['type' => 'paragraph', 'data' => ['text' => 'Laatste regel<script>alert(1)</script>']],
        ],
    ])]);

    $html = $product->descriptionHtml();
    $document = new DOMDocument();
    $document->loadHTML($html);

    expect($document->getElementsByTagName('p')->length)->toBe(3)
        ->and($document->getElementsByTagName('br')->length)->toBe(2)
        ->and($document->getElementsByTagName('script')->length)->toBe(0);

    $activity = new \App\Models\Activity(['description' => $product->description]);
    $document->loadHTML($activity->descriptionHTML);
    expect($document->getElementsByTagName('br')->length)->toBe(2);
});

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

it('keeps normal activities out of the shop activity list', function () {
    $activity = \App\Models\Activity::factory()->create([
        'title' => 'Spelletjesmiddag normaal', 'type' => \App\ActivityType::OneDay,
        'start' => now()->addDays(7), 'end' => now()->addDays(7)->endOfDay(),
    ]);
    $shopActivity = Product::factory()->create([
        'name' => 'Shopactiviteit apart', 'product_type' => 'activity', 'hidden' => false,
    ]);
    $this->get(route('product.activities'))->assertOk()->assertDontSee('Spelletjesmiddag normaal')
        ->assertDontSee(route('activity.show', $activity))
        ->assertSee('Shopactiviteit apart')->assertSee(route('product.show', $shopActivity));
    $this->get(route('activity.index'))->assertOk()->assertSee('Spelletjesmiddag normaal')
        ->assertSee(route('activity.show', $activity));
    $this->get(route('product.index'))->assertOk()->assertDontSee('Spelletjesmiddag normaal')
        ->assertDontSee('Shopactiviteit apart');
    $this->actingAs(User::factory()->admin()->create());
    $this->get(route('product.create', ['type' => 'activity']))->assertOk()->assertSee('Nieuwe activiteit')
        ->assertSee('data-editor-holder="description-editor"', false);
    $this->get(route('product.activities'))->assertOk()->assertSee(route('product.create', ['type' => 'activity']))
        ->assertSee('Deelnemers bekijken');
});

it('opens the normal activity screen without deleting the linked shop activity', function () {
    $this->actingAs(User::factory()->admin()->create());
    $product = Product::factory()->create([
        'product_type' => 'activity', 'hidden' => false, 'price' => 0, 'guest_price' => null,
        'stock' => null, 'max_participants' => 20, 'pickupLocation' => 'Stadhuis',
        'activity_starts_at' => now()->addWeek(), 'imagePath' => 'products/spelletjes.jpg',
    ]);
    $this->post(route('product.convertToActivity', $product))->assertSessionHas('success');
    $activity = \App\Models\Activity::where('title', $product->name)->sole();
    expect($activity->maxGuests)->toBe(1)->and($activity->maxParticipants)->toBe(20)
        ->and($activity->imagePath)->toBe('products/spelletjes.jpg')
        ->and(Product::find($product->id))->not->toBeNull();
    $this->get(route('activity.show', $activity))->assertOk()->assertSee('Introducees')
        ->assertSee('Nieuw aanmeldproces gebruiken');
});

it('keeps the same shop description when switching to the new signup process', function () {
    $this->actingAs(User::factory()->admin()->create());
    $product = switchableShopActivity([
        'description' => "Eerste regel\nDirect eronder\n\nVolgende alinea",
    ]);

    $this->post(route('product.convertToActivity', $product))->assertSessionHas('success');
    $activity = $product->fresh()->activity;
    $descriptionHtml = $product->fresh()->descriptionHtml();

    $this->get(route('activity.show', $activity))->assertOk()->assertSee($descriptionHtml, false);
    $this->post(route('product.useShopRegistration', $product))
        ->assertRedirect(route('product.show', $product));
    $this->get(route('product.show', $product))->assertOk()
        ->assertSee('data-shop-description', false)
        ->assertSee($descriptionHtml, false);

    expect($activity->fresh()->description)->toBe($activity->description);
});

it('uses compact editor spacing in both shop signup processes', function (bool $asAdmin) {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);
    $product = switchableShopActivity(['description' => json_encode([
        'time' => 1792475372086,
        'version' => '2.31.4',
        'blocks' => [
            ['type' => 'paragraph', 'data' => ['text' => 'Eerste regel<br>Direct eronder']],
            ['type' => 'paragraph', 'data' => ['text' => '']],
            ['type' => 'paragraph', 'data' => ['text' => 'Volgende alinea']],
        ],
    ])]);
    $this->post(route('product.convertToActivity', $product))->assertSessionHas('success');
    $activity = $product->fresh()->activity;

    foreach ([route('activity.show', $activity), route('product.show', $product)] as $url) {
        if ($url === route('product.show', $product)) {
            $this->actingAs($admin)->post(route('product.useShopRegistration', $product))->assertSessionHas('success');
        }
        if (!$asAdmin) {
            auth()->logout();
        }

        $response = $this->get($url)->assertOk();
        $document = new DOMDocument();
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $description = (new DOMXPath($document))->query('//*[@data-shop-description]')->item(0);

        expect($description)->not->toBeNull();
        expect($description->getAttribute('class'))->toContain('whitespace-normal')
            ->not->toContain('space-y-', 'whitespace-pre-line', 'flex');
        expect($description->getElementsByTagName('p')->length)->toBe(3)
            ->and($description->getElementsByTagName('br')->length)->toBe(1);
    }
})->with(['administrator' => true, 'visitor' => false]);

it('keeps incomplete shop orders safe and requires administrator access to switch', function () {
    $product = Product::factory()->create(['product_type' => 'activity', 'hidden' => false]);
    $this->post(route('product.convertToActivity', $product))->assertRedirect(route('home'));
    $admin = User::factory()->admin()->create();
    $order = Order::create(['user_id' => $admin->id]);
    $order->products()->attach($product, ['quantity' => 1]);
    $this->actingAs($admin)->post(route('product.convertToActivity', $product))->assertSessionHas('error');
    expect($product->fresh())->not->toBeNull()->and($order->fresh())->not->toBeNull();
});

function switchableShopActivity(array $overrides = []): Product
{
    return Product::factory()->create(array_merge([
        'product_type' => 'activity', 'hidden' => false, 'price' => 0, 'guest_price' => null,
        'stock' => null, 'max_participants' => 5, 'pickupLocation' => 'Stadhuis',
        'activity_starts_at' => now()->addWeek(),
        'registration_starts_at' => now()->subDay(), 'registration_ends_at' => now()->addDays(3),
    ], $overrides));
}

it('switches both ways repeatedly with the same participants payments and download', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();
    $product = switchableShopActivity(['price' => 15, 'stock' => 3]);
    $payment = Payment::create(['mollieId' => 'tr_preserve', 'description' => 'Bestelling #behouden', 'status' => PaymentStatus::paid, 'price' => 30]);
    $order = Order::create(['user_id' => $customer->id, 'payment_id' => $payment->id]);
    $order->products()->attach($product, ['quantity' => 2, 'unit_price' => 15]);

    $this->actingAs($admin)->post(route('product.convertToActivity', $product))->assertSessionHas('success');
    $activity = $product->fresh()->activity;
    $application = $activity->applications()->sole();
    expect($application->participants)->toBe(2)->and($application->status)->toBe(\App\ApplicationStatus::Active)
        ->and($application->calculateTotalCost())->toBe(30.0)->and($application->calculateTotalPaid())->toBe(30.0)
        ->and($payment->fresh()->application_id)->toBe($application->id)
        ->and($product->fresh()->availableQuantity())->toBe(3);

    $this->get(route('activity.show', $activity))->assertOk()->assertSee($customer->name)
        ->assertSee('Kostenoverzicht')->assertSee('Wie heeft wat betaald?')
        ->assertSee(route('admin.activities.download', $activity));
    $this->get(route('admin.activities.download', $activity))->assertOk()->assertDownload();
    $this->delete(route('activity.permanentDelete', $activity))->assertSessionHas('error');
    expect($order->fresh())->not->toBeNull()->and($payment->fresh())->not->toBeNull();
    $this->get(route('activity.index'))->assertOk()->assertDontSee(route('activity.show', $activity));

    for ($round = 0; $round < 2; $round++) {
        $this->post(route('product.useShopRegistration', $product))->assertRedirect(route('product.show', $product));
        $this->get(route('product.show', $product))->assertOk()->assertSee('Deelnemers en kostenoverzicht');
        $this->post(route('product.convertToActivity', $product))->assertRedirect(route('activity.show', $activity));
    }
    $activity->update(['price' => 50]);
    expect($application->fresh()->calculateTotalCost())->toBe(30.0)
        ->and($activity->applications()->count())->toBe(1)->and(Payment::count())->toBe(1)
        ->and(Order::count())->toBe(1)->and(\App\Models\Activity::count())->toBe(1);

    $this->actingAs($customer)->post(route('product.useShopRegistration', $product))->assertRedirect(route('home'));
    expect($product->fresh()->normal_registration)->toBeTrue();
});

it('shares capacity and registrations between the new and normal signup flows', function () {
    \Illuminate\Support\Facades\Mail::fake();
    Mollie::shouldReceive('api')->never();
    foreach (['email-activiteit-aangemeld', 'email-activiteit-aangemeld-reserve', 'email-bestelling-betaald', 'email-bestuur-nieuwe-bestelling'] as $name) {
        \App\Models\Content::create(['type' => 'email', 'name' => $name, 'title' => 'Aanmelding', 'text' => '']);
    }
    $admin = User::factory()->admin()->create();
    $product = switchableShopActivity(['max_participants' => 3]);
    $this->actingAs($admin)->post(route('product.convertToActivity', $product))->assertSessionHas('success');
    $activity = $product->fresh()->activity;
    $this->post(route('product.useShopRegistration', $product))->assertSessionHas('success');
    $customer = User::factory()->create();
    $this->actingAs($customer)->post(route('product.purchase', $product), ['quantity' => 2])->assertSessionHas('success');
    expect($activity->applications()->sole()->participants)->toBe(2)
        ->and($product->fresh()->availableQuantity())->toBe(1);
    $this->get(route('product.show', $product))->assertOk()->assertSee('Je staat al op de deelnemerslijst');
    $this->post(route('product.purchase', $product), ['quantity' => 1])->assertSessionHas('error');

    $this->actingAs($admin)->post(route('product.convertToActivity', $product))->assertSessionHas('success');
    $second = User::factory()->create();
    $this->actingAs($second)->post(route('application.store', $activity), ['email' => $second->email, 'phone' => '0612345678'])
        ->assertSessionHasNoErrors()->assertSessionHas('success');
    expect($activity->fresh()->participants->capacity)->toBe(0);
    $this->actingAs($admin)->post(route('product.useShopRegistration', $product))->assertSessionHas('success');
    $this->actingAs(User::factory()->create())->post(route('product.purchase', $product), ['quantity' => 1])->assertSessionHas('error');
    expect($activity->applications()->sum('participants'))->toBe(3)->and(Order::count())->toBe(1);

    \App\Models\Content::firstOrCreate(['name' => 'email-activiteit-afgemeld'], ['type' => 'email', 'title' => 'Afmelding', 'text' => '']);
    $this->actingAs($admin)->delete(route('application.destroy', $activity->applications()->where('user_id', $customer->id)->sole()))
        ->assertSessionHas('success');
    expect($product->fresh()->availableQuantity())->toBe(2);
});

it('retains questions in the new layout after switching back from normal registration', function () {
    \Illuminate\Support\Facades\Mail::fake();
    Mollie::shouldReceive('api')->never();
    foreach (['email-activiteit-aangemeld', 'email-activiteit-aangemeld-reserve'] as $name) {
        \App\Models\Content::firstOrCreate(['name' => $name], ['type' => 'email', 'title' => 'Aanmelding', 'text' => '']);
    }
    $product = switchableShopActivity();
    $this->actingAs(User::factory()->admin()->create())->post(route('product.convertToActivity', $product));
    $activity = $product->fresh()->activity;
    $question = $activity->questions()->create(['type' => 'text', 'query' => 'Dieetwensen', 'price' => 0]);
    $this->post(route('product.useShopRegistration', $product));
    $member = User::factory()->create();
    $this->actingAs($member)->get(route('product.show', $product))->assertOk()->assertSee('Dieetwensen')
        ->assertSee(route('application.store', $activity));
    $this->post(route('product.purchase', $product), ['quantity' => 1])->assertSessionHas('error');
    $this->post(route('application.store', $activity), ['email' => $member->email, 'phone' => '0612345678', 'questions' => [$question->id => 'Geen']])
        ->assertSessionHasNoErrors()->assertSessionHas('success');
    expect($activity->applications()->sole()->answers()->sole()->answer)->toBe('Geen');
});

it('rejects stale registration forms and keeps hidden linked activities private', function () {
    $admin = User::factory()->admin()->create();
    $product = switchableShopActivity();
    $this->actingAs($admin)->post(route('product.convertToActivity', $product));
    $activity = $product->fresh()->activity;
    $this->post(route('product.purchase', $product), ['quantity' => 1])->assertRedirect(route('activity.show', $activity));
    $this->post(route('product.useShopRegistration', $product));
    $this->post(route('application.store', $activity), ['email' => $admin->email, 'phone' => '0612345678'])
        ->assertRedirect(route('product.show', $product));
    expect($activity->applications()->count())->toBe(0)->and(Order::count())->toBe(0);
    $product->update(['hidden' => true]);
    $this->actingAs(User::factory()->create())->get(route('activity.show', $activity))->assertNotFound();
});

it('rolls back the link rather than stealing a multi-item payment', function () {
    $product = switchableShopActivity();
    $admin = User::factory()->admin()->create();
    $payment = Payment::create(['mollieId' => 'tr_multi', 'description' => 'Bestelling #combinatie', 'status' => PaymentStatus::paid, 'price' => 20]);
    $order = Order::create(['user_id' => $admin->id, 'payment_id' => $payment->id]);
    $order->products()->attach($product, ['quantity' => 1, 'unit_price' => 10]);
    $order->products()->attach(Product::factory()->create(), ['quantity' => 1, 'unit_price' => 10]);
    $this->actingAs($admin)->post(route('product.convertToActivity', $product))->assertSessionHas('error');
    expect($product->fresh()->activity_id)->toBeNull()->and($payment->fresh()->application_id)->toBeNull()
        ->and(\App\Models\Activity::count())->toBe(0)->and($order->products()->count())->toBe(2);
});

it('keeps copies of linked shop activities in Shop Overig without copying participants', function () {
    $product = switchableShopActivity();
    $this->actingAs(User::factory()->admin()->create())->post(route('product.convertToActivity', $product));
    $activity = $product->fresh()->activity;
    $this->post(route('activity.copy', $activity))->assertSessionHas('success');
    $copy = Product::where('name', $product->name . ' (kopie)')->sole();
    expect($copy->hidden)->toBeTrue()->and($copy->normal_registration)->toBeTrue()
        ->and($copy->activity_id)->not->toBe($activity->id)->and($copy->activity->applications()->count())->toBe(0);
    $this->post(route('product.copy', $product))->assertSessionHas('success');
    expect(Product::where('name', $product->name . ' (kopie)')->whereNull('activity_id')->count())->toBe(1);
});

it('shows an existing image and upload preview in the normal create and edit forms', function () {
    $activity = \App\Models\Activity::factory()->create(['imagePath' => 'images/activities/existing.jpg']);
    $this->actingAs(User::factory()->admin()->create());
    $this->get(route('activity.create'))->assertOk()->assertSee('data-image-preview="image-upload"', false);
    $this->get(route('activity.edit', $activity))->assertOk()->assertSee('data-image-preview="image-upload"', false)
        ->assertSee('src="' . $activity->image . '"', false)->assertSee('Beschrijving en afbeelding');
    $this->get(route('product.create'))->assertOk()->assertSee('data-image-preview="imagePath"', false);
});

it('does not turn a sold-out shop activity into an unlimited activity', function () {
    $this->actingAs(User::factory()->admin()->create());
    $product = Product::factory()->create([
        'product_type' => 'activity', 'hidden' => false, 'price' => 0, 'guest_price' => null,
        'stock' => 0, 'max_participants' => 20, 'pickupLocation' => 'Stadhuis',
        'activity_starts_at' => now()->addWeek(),
    ]);
    $this->post(route('product.convertToActivity', $product))->assertSessionHas('error');
    expect($product->fresh())->not->toBeNull();
    $this->assertDatabaseCount('activities', 0);
});

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
