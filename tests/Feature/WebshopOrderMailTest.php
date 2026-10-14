<?php

use App\Mail\BoardNewOrder;
use App\Mail\OrderPaid;
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
