<?php

namespace App\Models;

use DateTime;
use Exception;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use App\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Laravel\Facades\Mollie;
use Nette\NotImplementedException;

class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;


    // protected string $webhookUrl = route('payment.webhook');
    // protected static string $webhookUrl = 'https://4b34-212-178-86-206.ngrok-free.app/mollie/webhook';
    protected static string $webhookUrl;

    // Set the default webhook URL for the payment model.
    // Using booted() to avoid issues with constructor & factory creation.
    public static function booted()
    {
        self::$webhookUrl = route('payment.webhook');
    }

    protected $fillable = [
        'mollieId',
        'description',
        'status',
        'price',
        'isPaymentLink',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'paidAt' => 'datetime',
        'refundedAt' => 'datetime',
        'price' => 'decimal:2',
        'refundedAmount' => 'decimal:2',
        'status' => PaymentStatus::class,
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function orders()
    {
        return $this->hasOne(Order::class);
    }

    // This function creates a payment in Mollie and stores it in the database
    public static function generatePayment($price, $description, $applicationId, $metadata = null): Payment
    {
        // Ensure the price is a valid decimal number
        $price = number_format((float)$price, 2, '.', '');

        // Store the payment in the database
        $payment = Payment::create([
            'mollieId' => 'temp',
            'description' => $description,
            'status' => PaymentStatus::pending,
            'price' => $price,
        ]);

        // Make a payment request to Mollie
        $molliePayment = Mollie::api()->payments->create([
            'amount' => [
                'currency' => 'EUR',
                'value' => strval($price),
            ],
            'description' => $description,
            'metadata' => $metadata,
            'redirectUrl' => route('payment.status', $payment->id),
            'webhookUrl' => self::$webhookUrl,
        ]);

        if (isset($applicationId)) {
            $payment->application_id = $applicationId;
        }

        $payment->mollieId = $molliePayment->id;
        $payment->save();

        return $payment;
    }

    /**
     * Generates a Mollie payment link and creates a corresponding Payment record in the database.
     *
     * @param float $price The payment amount in EUR.
     * @param string $description Description of the payment.
     * @param DateTime|null $expiresAt Optional expiration date and time for the payment link.
     * @param int|null $applicationId The ID of the related application.
     * @param array $metadata Optional metadata to attach to the payment.
     * @return self The created Payment model instance with updated Mollie payment link information.
     */
    public static function generatePaymentLink(float $price, string $description, DateTime $expiresAt = null, int $applicationId = null, array $metadata = [])
    {
        // Ensure the price is a valid decimal number
        $price = number_format((float)$price, 2, '.', '');

        // Create a payment in the database
        $payment = self::create([
            'mollieId' => 'temp',
            'description' => $description,
            'status' => PaymentStatus::pending,
            'price' => $price,
            'isPaymentLink' => true,
        ]);

        // Create a payment link in Mollie
        $molliePayment = Mollie::api()->paymentLinks->create([
            'amount' => [
                'currency' => 'EUR',
                'value' => strval($price),
            ],
            'description' => $description,
            'metadata' => $metadata,
            'expiresAt' => $expiresAt?->format(DateTime::ATOM),
            'redirectUrl' => route('payment.status', $payment->id),
            'webhookUrl' => self::$webhookUrl,
        ]);

        if (isset($applicationId)) {
            $payment->application_id = $applicationId;
        }

        $payment->mollieId = $molliePayment->id;
        $payment->save();

        return $payment;
    }

    public function getPrice()
    {
        return $this->price - $this->refundedAmount;
    }

    /**
     * Return true if the payment is fully refunded
     */
    protected function refunded(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->refundedAmount === $this->price,
        );
    }

    /**
     * The amount that can be refunded.
     */
    protected function refundableAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->price - ($this->refundedAmount ?? 0),
        );
    }

    /**
     * Refunds the payment. If no amount is specified, refunds the full remaining amount.
     *
     * @param float|null $amount The amount to refund. If null, refunds the full remaining amount.
     * @return float The refunded amount.
     * @throws Exception If the payment is already refunded.
     * @throws Exception If the refund amount exceeds the total price.
     * @throws Exception If the Mollie payment is not found.
     */
    public function refund(float $amount = null): float
    {
        if($this->refunded) {
            throw new Exception('Payment is already refunded.');
        }

        // If no amount is given, set amount to the full remaining amount
        if ($amount === null) {
            $amount = $this->refundableAmount;
        }

        // Explicitly set refundedAmount to 0 if it is null
        $refundedAmount = $this->refundedAmount ?? 0;

        // Check if the amount plus the refunded amount exceeds the total price
        if ($refundedAmount + $amount > $this->price) {
            throw new Exception('Refund amount exceeds the total price.');
        }

        // Get the payment in Mollie
        // If it does not exist, throw an exception
        try {
            //Experimental
            // Retrieve all payments created via this payment link.
            if($this->isPaymentLink) {
                $payments = Mollie::api()->paymentLinkPayments->iteratorForId($this->mollieId)->all();
                $payment = null;

                // Maybe check if payments is empty? Not sure.
                if (empty($payments)) {
                    throw new Exception('No payments found for this payment link.');
                }

                // Get the first paid payment.
                foreach ($payments as $p) {
                    if ($p->isPaid()) {
                        $payment = $p;
                        break;
                    }
                }

                // Get the first one if no paid payments are found.
                $payment = $payment ?? $payments[0];
            } else {
                $payment = Mollie::api()->payments->get($this->mollieId);
            }

        } catch (ApiException $e) {
            throw new Exception('[PaymentModel]Mollie payment not found: ' . $e->getMessage());
        }

        // Refund the payment in Mollie
        Mollie::api()->payments->refund($payment, ['amount' => ['currency' => 'EUR','value' => number_format($amount, 2, '.', '')]]);

        // Update the refunded amount in the database
        $this->refundedAmount = $refundedAmount + $amount;
        $this->refundedAt = now();
        $this->save();

        return $amount;
    }
}
