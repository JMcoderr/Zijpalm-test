<?php
// This file is part of the app logic and has a short comment so it is easier to read.


namespace App\Models;

use App\Mail\BoardNewMembers;
use App\Mail\BoardNewOrder;
use App\Mail\NewMember;
use App\Mail\OrderPaid;
use App\Mail\PaymentFailed;
use App\PaymentStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Mollie\Laravel\Facades\Mollie;
//use Log;

class Order extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'payment_id',
        'is_guest_checkout',
    ];

    protected $casts = [
        'is_guest_checkout' => 'boolean',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'order_products')->withPivot('quantity', 'unit_price');
    }

    public static function createWithProducts($products = [['productId', 'quantity']], $paymentDescription, User $user, bool $newMember = false): Order
    {
        return DB::transaction(function () use ($products, $paymentDescription, $user, $newMember) {
            // Initialize the price var
            $price = 0.00;
            $lockedProducts = [];

            // Lock products first so stock checks and updates stay safe under concurrent requests.
            foreach ($products as $product) {
                $quantity = (int) ($product['quantity'] ?? 0);
                $productModel = Product::query()->whereKey($product['productId'])->lockForUpdate()->firstOrFail();

                if ($productModel->stock !== null && $quantity > (int) $productModel->stock) {
                    throw new \RuntimeException('Niet genoeg voorraad beschikbaar.');
                }

                $price += (float) $productModel->price * $quantity;
                $lockedProducts[] = [
                    'model' => $productModel,
                    'quantity' => $quantity,
                ];
            }

            // Format the price
            $price = (string) number_format($price, 2, '.');

            $order = self::create([
                'user_id' => $user->id,
                'is_guest_checkout' => false,
            ]);

            foreach ($lockedProducts as $product) {
                $order->products()->attach($product['model']->id, [
                    'quantity' => $product['quantity'],
                ]);

                if ($product['model']->stock !== null) {
                    $product['model']->decrement('stock', $product['quantity']);
                }
            }

            // Create a metadata array for the payment
            // This will be used to indentify the order and if the user is a new member
            $metadata = [
                'orderId' => $order->id,
                'newMember' => $newMember,
            ];

            $payment = Payment::generatePayment(
                $price,
                $paymentDescription,
                $metadata['newMember'] ? null : $order->id,
                $metadata
            );

            // Set the payment id on the order
            $order->payment_id = $payment->id;

            $order->save();

            return $order;
        });
    }

    // This function is called when the payment status changes
    public function handleNewStatus(Payment $payment)
    {
        // Get the payment from Mollie
        $molliePayment = Mollie::api()->payments->get($payment->mollieId);

        // Check if the payment has metadata for new member
        // If it does, we will use it to send a different email
        // and delete the user if the payment failed
        $metadata = $molliePayment->metadata;

        // Decode if necessary
        if (is_string($metadata)) {
            $metadata = json_decode($metadata);
        }

        $newMember = $metadata->newMember ?? false;

        // Send confirmation email only when payment is definitively paid.
        if ($payment->status === PaymentStatus::paid){
            // If the user is a new member, send the new member email
            if ($newMember) {
                Mail::to(config('mail.bestuur.address'), config('mail.bestuur.name'))->send(new NewMember($this->user));

                // Send an email to the board with the new member
                Mail::to(config('mail.bestuur.address'), config('mail.bestuur.name'))->send(new BoardNewMembers(User::find(1), new Collection([$this->user])));
            } else { // Send the order paid email
                Mail::to(config('mail.bestuur.address'), config('mail.bestuur.name'))->send(new OrderPaid($this));
                Mail::to(config('mail.bestuur.address'), config('mail.bestuur.name'))->send(new BoardNewOrder(User::find(1), $this));
            }

            return;
        }

        // Keep order as-is for non-terminal statuses.
        if (in_array($payment->status, [PaymentStatus::open, PaymentStatus::pending, PaymentStatus::authorized], true)) {
            return;
        }

        // Payment failed/canceled/expired: release reserved stock and clean up order.
        if (in_array($payment->status, [PaymentStatus::failed, PaymentStatus::canceled, PaymentStatus::expired], true)) {
            Log::debug("[OrderModel]Payment failed Errors: payment_id {$payment->id}, status: {$payment->status->value}, mollie_id {$payment->mollieId}");

            foreach ($this->products as $product) {
                if ($product->stock !== null) {
                    $product->increment('stock', (int) $product->pivot->quantity);
                }
            }

            // Send the payment failed email
            Mail::to(config('mail.bestuur.address'), config('mail.bestuur.name'))->send(new PaymentFailed($payment, $this->user));

            // Permanently delete the order and the user if it's a new member
            $this->delete();
            if ($newMember) {
                $this->user()->delete();
            }

            return;
        }
    }
}
