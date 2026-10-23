<?php

use App\Mail\BoardNewOrder;
use App\Mail\OrderPaid;
use App\Models\Activity;
use App\Models\Application;
use App\Models\Content;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mollie\Api\MollieApiClient;
use Mollie\Laravel\Facades\Mollie;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['mail.bestuur.address' => 'board@example.com', 'mail.bestuur.name' => 'Zijpalm']);
    Content::create([
        'type' => 'email', 'name' => 'email-bestelling-betaald',
        'title' => 'Uw bestelling is betaald!',
        'text' => 'Uw bestelling is betaald! Hieronder vindt u de details van uw bestelling:',
    ]);
    Content::create([
        'type' => 'email', 'name' => 'email-bestuur-nieuwe-bestelling',
        'title' => 'Nieuwe bestelling', 'text' => 'Een nieuwe betaalde bestelling.',
    ]);
    $this->customer = User::factory()->create(['firstName' => 'Test', 'lastName' => 'Klant']);
    $this->payment = Payment::create([
        'mollieId' => 'tr_notification', 'status' => PaymentStatus::pending,
        'description' => 'Bestelling #test', 'price' => 31,
    ]);
    $this->order = Order::create([
        'user_id' => $this->customer->id, 'payment_id' => $this->payment->id,
        'is_guest_checkout' => false,
    ]);
    $this->product = Product::factory()->create(['name' => 'Badhanddoek Zijpalm', 'price' => 15.5]);
    $this->order->products()->attach($this->product, ['quantity' => 2, 'unit_price' => 15.5]);
});

function mockWebshopPaidPayment(): void
{
    $remotePayment = Mockery::mock();
    $remotePayment->status = 'paid';
    $remotePayment->paidAt = now()->toIso8601String();
    $remotePayment->metadata = (object) ['newMember' => false];
    $remotePayment->shouldReceive('isPaid')->andReturnTrue();
    $endpoint = Mockery::mock();
    $endpoint->shouldReceive('get')->with('tr_notification')->andReturn($remotePayment);
    $client = Mockery::mock(MollieApiClient::class);
    $client->payments = $endpoint;
    Mollie::shouldReceive('api')->andReturn($client);
}

it('notifies the board and routes the customer confirmation through Power Automate once', function () {
    Mail::fake();
    mockWebshopPaidPayment();

    $this->post(route('payment.webhook'), ['id' => 'tr_notification'])->assertOk();
    $this->post(route('payment.webhook'), ['id' => 'tr_notification'])->assertOk();

    expect($this->payment->fresh()->status)->toBe(PaymentStatus::paid);
    Mail::assertSent(OrderPaid::class, fn ($mail) =>
        $mail->hasTo('board@example.com') && $mail->user->id === $this->customer->id);
    Mail::assertSent(BoardNewOrder::class, fn ($mail) =>
        $mail->hasTo('board@example.com') && $mail->user->id === $this->customer->id);
    Mail::assertSentCount(2);

    $mail = new OrderPaid($this->order);
    $body = json_decode($mail->content()->with['jsonBody'], true, flags: JSON_THROW_ON_ERROR);
    expect($body['email'])->toBe($this->customer->email)
        ->and($body['subject'])->toBe('Je bestelling is betaald! #Z')
        ->and($body['body'])->toContain('Je bestelling is betaald!')
        ->toContain('Hieronder vind je de details van je bestelling:')
        ->toContain('Voor het ophalen van je bestelling nemen we contact met je op.')
        ->not->toContain('Totaal: ')
        ->and(substr_count($body['body'], 'Totaalbedrag:'))->toBe(1);

    $boardHtml = (new BoardNewOrder($this->customer, $this->order))->render();
    expect($boardHtml)->toContain($this->customer->email)->toContain('Test Klant');
});

it('leaves a failed notification retryable instead of permanently skipping it', function () {
    mockWebshopPaidPayment();
    $mailManager = Mail::getFacadeRoot();
    Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('Mail server unavailable'));
    $this->withoutExceptionHandling();

    try {
        $this->post(route('payment.webhook'), ['id' => 'tr_notification']);
        test()->fail('Expected a mail transport error.');
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('Mail server unavailable');
    }

    expect($this->payment->fresh()->status)->toBe(PaymentStatus::pending)
        ->and($this->order->fresh())->not->toBeNull();

    Mail::swap($mailManager);
    Mail::fake();
    $this->post(route('payment.webhook'), ['id' => 'tr_notification'])->assertOk();
    Mail::assertSent(OrderPaid::class);
    Mail::assertSent(BoardNewOrder::class);
});

it('keeps applications pending through Mollie statuses and restores late paid registrations', function (bool $capacityAvailable) {
    Mail::fake();
    Content::create([
        'type' => 'email', 'name' => 'email-activiteit-aangemeld',
        'title' => 'Inschrijving bevestigd', 'text' => 'Je inschrijving is bevestigd.',
    ]);
    Content::create([
        'type' => 'email', 'name' => 'email-activiteit-aangemeld-reserve',
        'title' => 'Reservering', 'text' => 'Je staat op de reservelijst.',
    ]);
    Content::firstOrCreate(['name' => 'email-betaling-mislukt'], [
        'type' => 'email', 'title' => 'Betaling mislukt', 'text' => 'Je betaling is niet gelukt.',
    ]);
    $activity = Activity::factory()->create(['maxParticipants' => $capacityAvailable ? 25 : 2, 'price' => 25]);
    if (!$capacityAvailable) {
        Application::create([
            'activity_id' => $activity->id,
            'user_id' => User::factory()->create()->id,
            'participants' => 2,
            'email' => 'existing@example.com',
            'phone' => '0612345678',
            'status' => \App\ApplicationStatus::Active,
        ]);
    }
    $application = Application::create([
        'activity_id' => $activity->id,
        'user_id' => $this->customer->id,
        'participants' => 2,
        'email' => $this->customer->email,
        'phone' => $this->customer->phone ?? '0612345678',
        'status' => \App\ApplicationStatus::Pending,
    ]);
    $payment = Payment::create([
        'mollieId' => 'tr_activity_status_transition',
        'status' => PaymentStatus::pending,
        'description' => 'Inschrijving met introducee',
        'price' => 25,
    ]);
    $payment->application()->associate($application);
    $payment->save();

    $remotePayments = [];
    foreach (['open', 'pending', 'authorized', 'failed', 'paid'] as $status) {
        $remote = Mockery::mock();
        $remote->status = $status;
        $remote->paidAt = $status === 'paid' ? now()->toIso8601String() : null;
        $remote->shouldReceive('isPaid')->andReturn($status === 'paid');
        $remotePayments[] = $remote;
    }
    $endpoint = Mockery::mock();
    $endpoint->shouldReceive('get')->with('tr_activity_status_transition')->times(5)->andReturn(...$remotePayments);
    $client = Mockery::mock(MollieApiClient::class);
    $client->payments = $endpoint;
    Mollie::shouldReceive('api')->times(5)->andReturn($client);

    foreach ([
        'open' => \App\ApplicationStatus::Pending,
        'pending' => \App\ApplicationStatus::Pending,
        'authorized' => \App\ApplicationStatus::Pending,
        'failed' => \App\ApplicationStatus::Cancelled,
        'paid' => $capacityAvailable ? \App\ApplicationStatus::Active : \App\ApplicationStatus::Reserve,
    ] as $status => $expectedStatus) {
        $this->post(route('payment.webhook'), ['id' => 'tr_activity_status_transition'])->assertOk();
        expect($payment->fresh()->status)->toBe(PaymentStatus::from($status))
            ->and($application->fresh()->status)->toBe($expectedStatus);
    }

    Mail::assertSent(\App\Mail\PaymentFailed::class);
    Mail::assertSent(\App\Mail\ActivityApplied::class, fn ($mail) => $mail->reserve === !$capacityAvailable);
})->with([
    'capacity is available' => true,
    'activity fills while payment is unresolved' => false,
]);

it('does not restore a second late-paid registration for the same activity', function () {
    Mail::fake();
    $activity = Activity::factory()->create(['maxParticipants' => 25, 'price' => 25]);
    $application = Application::create([
        'activity_id' => $activity->id,
        'user_id' => $this->customer->id,
        'participants' => 2,
        'email' => $this->customer->email,
        'phone' => $this->customer->phone ?? '0612345678',
        'status' => \App\ApplicationStatus::Cancelled,
    ]);
    $payment = Payment::create([
        'mollieId' => 'tr_duplicate_late_paid',
        'status' => PaymentStatus::paid,
        'description' => 'Tweede betaalpoging',
        'price' => 25,
    ]);
    $payment->application()->associate($application);
    $payment->save();
    Application::create([
        'activity_id' => $activity->id,
        'user_id' => $this->customer->id,
        'participants' => 2,
        'email' => $this->customer->email,
        'phone' => $this->customer->phone ?? '0612345678',
        'status' => \App\ApplicationStatus::Active,
    ]);

    $application->handleNewStatus($payment, PaymentStatus::failed);

    expect($application->fresh()->status)->toBe(\App\ApplicationStatus::Cancelled)
        ->and($activity->applications()->where('status', \App\ApplicationStatus::Active)->count())->toBe(1);
    Mail::assertNotSent(\App\Mail\ActivityApplied::class);
});

it('keeps an active organizer application when its guest payment succeeds', function () {
    Mail::fake();
    Content::create([
        'type' => 'email', 'name' => 'email-activiteit-aangemeld',
        'title' => 'Inschrijving bevestigd', 'text' => 'Je inschrijving is bevestigd.',
    ]);
    Content::create([
        'type' => 'email', 'name' => 'email-activiteit-aangemeld-reserve',
        'title' => 'Reservering', 'text' => 'Je staat op de reservelijst.',
    ]);
    $activity = Activity::factory()->create(['maxParticipants' => 25, 'price' => 25]);
    $application = Application::create([
        'activity_id' => $activity->id,
        'user_id' => $this->customer->id,
        'participants' => 2,
        'email' => $this->customer->email,
        'phone' => $this->customer->phone ?? '0612345678',
        'status' => \App\ApplicationStatus::Active,
    ]);
    $payment = Payment::create([
        'mollieId' => 'tr_organizer_guest_paid',
        'status' => PaymentStatus::paid,
        'description' => 'Organisator met betalende gast',
        'price' => 25,
    ]);
    $payment->application()->associate($application);
    $payment->save();

    $application->handleNewStatus($payment, PaymentStatus::pending);
    $application->handleNewStatus($payment, PaymentStatus::paid);

    expect($application->fresh()->status)->toBe(\App\ApplicationStatus::Active);
    Mail::assertSent(\App\Mail\ActivityApplied::class, 1);
    Mail::assertNotSent(\App\Mail\PaymentFailed::class);
});

it('does not put a pickup promise in an activity-only confirmation and retains multi-item subtotals', function () {
    $this->product->update(['product_type' => 'activity']);
    $html = view('mail.order-paid', [
        'order' => $this->order->fresh(), 'user' => $this->customer,
        'content' => Content::where('name', 'email-bestelling-betaald')->firstOrFail(),
    ])->render();
    expect($html)->not->toContain('Voor het ophalen van je bestelling');

    $other = Product::factory()->create(['name' => 'Tas Zijpalm']);
    $this->order->products()->attach($other, ['quantity' => 1, 'unit_price' => 5]);
    $html = view('mail.order-paid', [
        'order' => $this->order->fresh(), 'user' => $this->customer,
        'content' => Content::where('name', 'email-bestelling-betaald')->firstOrFail(),
    ])->render();
    expect(substr_count($html, 'Subtotaal:'))->toBe(2)
        ->and(substr_count($html, 'Totaalbedrag:'))->toBe(1)
        ->and($html)->toContain('Voor het ophalen van je bestelling');
});

it('updates linked shop participants on webhooks without duplicate activity emails', function (string $status) {
    Mail::fake();
    $this->product->update([
        'product_type' => 'activity', 'hidden' => false, 'stock' => null, 'guest_price' => null,
        'pickupLocation' => 'Stadhuis', 'activity_starts_at' => now()->addWeek(), 'max_participants' => 5,
    ]);
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('product.convertToActivity', $this->product))->assertSessionHas('success');
    $application = $this->product->fresh()->activity->applications()->sole();

    $remote = Mockery::mock();
    $remote->status = $status;
    $remote->paidAt = $status === 'paid' ? now()->toIso8601String() : null;
    $remote->metadata = (object) ['newMember' => false];
    $remote->shouldReceive('isPaid')->andReturn($status === 'paid');
    $endpoint = Mockery::mock();
    $endpoint->shouldReceive('get')->with('tr_notification')->andReturn($remote);
    $client = Mockery::mock(MollieApiClient::class);
    $client->payments = $endpoint;
    Mollie::shouldReceive('api')->andReturn($client);
    if ($status === 'failed') {
        Content::firstOrCreate(['name' => 'email-betaling-mislukt'], ['type' => 'email', 'title' => 'Betaling', 'text' => '']);
    }

    $this->post(route('payment.webhook'), ['id' => 'tr_notification'])->assertOk();
    $this->post(route('payment.webhook'), ['id' => 'tr_notification'])->assertOk();
    expect($application->fresh()->status)->toBe(match ($status) {
        'paid' => \App\ApplicationStatus::Active,
        'failed' => \App\ApplicationStatus::Cancelled,
        default => \App\ApplicationStatus::Pending,
    });
    Mail::assertNotSent(\App\Mail\ActivityApplied::class);
    if ($status === 'paid') {
        expect($application->fresh()->calculateTotalPaid())->toBe(31.0);
        Mail::assertSentCount(2);
    }
})->with(['authorized', 'paid', 'failed']);
