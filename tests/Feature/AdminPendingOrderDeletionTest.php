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

it('allows admins to delete pending member and guest orders and releases stock', function () {
    $admin = User::factory()->admin()->create();
    $paymentsEndpoint = Mockery::mock();
    $paymentsEndpoint->shouldReceive('get')->twice()->andReturn(
        (object) ['status' => PaymentStatus::open->value],
        (object) ['status' => PaymentStatus::expired->value],
    );
    $paymentsEndpoint->shouldReceive('cancel')->once()->andReturnNull();
    $mollieClient = Mockery::mock(MollieApiClient::class);
    $mollieClient->payments = $paymentsEndpoint;
    Mollie::shouldReceive('api')
        ->times(3)
        ->andReturn($mollieClient);

    $orders = [];

    foreach ([false, true] as $isGuestCheckout) {
        $user = User::factory()->create([
            'password' => $isGuestCheckout ? null : 'password',
        ]);
        $product = Product::factory()->create(['stock' => 5]);
        $order = Order::create([
            'user_id' => $user->id,
            'is_guest_checkout' => $isGuestCheckout,
        ]);
        $payment = Payment::create([
            'mollieId' => 'tr_pending_'.$order->id,
            'description' => "Bestelling #{$order->id} - {$product->name}",
            'status' => PaymentStatus::pending,
            'price' => 20,
        ]);

        $order->payment_id = $payment->id;
        $order->save();
        $order->products()->attach($product->id, [
            'quantity' => 2,
            'unit_price' => $product->price,
        ]);
        $product->update(['stock' => 3]);
        $orders[] = compact('order', 'payment', 'product', 'user');
    }

    $this->actingAs($admin)
        ->get(route('admin.orders'))
        ->assertOk()
        ->assertSee('Ledenbestellingen')
        ->assertSee('Gastbestellingen')
        ->assertSee('aria-label="Bestelling verwijderen"', false);

    foreach ($orders as $items) {
        $this->delete(route('admin.orders.destroy', $items['order']))
            ->assertRedirect(route('admin.orders'))
            ->assertSessionHas('success');

        expect(Order::find($items['order']->id))->toBeNull()
            ->and($items['payment']->fresh()->status)->toBe(PaymentStatus::canceled)
            ->and($items['product']->fresh()->stock)->toBe(5)
            ->and($items['user']->fresh())->not->toBeNull();
    }
});

it('does not allow admins to delete paid orders', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $order = Order::create([
        'user_id' => $user->id,
        'is_guest_checkout' => false,
    ]);
    $payment = Payment::create([
        'mollieId' => 'tr_paid_'.$order->id,
        'description' => "Bestelling #{$order->id} - Paid product",
        'status' => PaymentStatus::paid,
        'price' => 20,
    ]);
    $order->payment_id = $payment->id;
    $order->save();

    Mollie::shouldReceive('api')->never();

    $this->actingAs($admin)
        ->delete(route('admin.orders.destroy', $order))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(Order::find($order->id))->not->toBeNull()
        ->and($payment->fresh()->status)->toBe(PaymentStatus::paid);
});

it('does not delete an order Mollie reports as paid', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $order = Order::create([
        'user_id' => $user->id,
        'is_guest_checkout' => false,
    ]);
    $payment = Payment::create([
        'mollieId' => 'tr_remote_paid_'.$order->id,
        'description' => "Bestelling #{$order->id} - Paid product",
        'status' => PaymentStatus::pending,
        'price' => 20,
    ]);
    $order->payment_id = $payment->id;
    $order->save();

    $paymentsEndpoint = Mockery::mock();
    $paymentsEndpoint->shouldReceive('get')->once()->andReturn((object) [
        'status' => PaymentStatus::paid->value,
    ]);
    $paymentsEndpoint->shouldNotReceive('cancel');
    $mollieClient = Mockery::mock(MollieApiClient::class);
    $mollieClient->payments = $paymentsEndpoint;
    Mollie::shouldReceive('api')->once()->andReturn($mollieClient);

    $this->actingAs($admin)
        ->delete(route('admin.orders.destroy', $order))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(Order::find($order->id))->not->toBeNull()
        ->and($payment->fresh()->status)->toBe(PaymentStatus::paid);
});
