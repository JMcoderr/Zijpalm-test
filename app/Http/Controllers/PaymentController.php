<?php
// This file is part of the app logic and has a short comment so it is easier to read.


namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use App\PaymentStatus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
//use Log;
use Mollie\Laravel\Facades\Mollie;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Payment $payment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Payment $payment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Payment $payment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payment $payment)
    {
        //
    }


    public function status(int $paymentId)
    {
        // Load the payment record from the database first.
        $payment = Payment::findOrFail($paymentId);

        // Check if this is a payment link or a normal payment.
        // The Mollie API is different for both cases, so we handle them separately.
        if ($payment->isPaymentLink) {
            $payments = Mollie::api()->paymentLinkPayments->iteratorForId($payment->mollieId)->all();

            // Look through all linked payments and prefer the first one that is paid.
            foreach ($payments as $p) {
                if ($p->status === 'paid') {
                    $paymentMollie = $p;
                    break;
                }
            }

            // If none of them is paid yet, use the first one so we still have a status.
            if (!isset($paymentMollie)) {
                count($payments) === 0 ? $paymentMollie = null : $paymentMollie = $payments[0];
            }
        } else {
            $paymentMollie = Mollie::api()->payments->get($payment->mollieId);
        }

        $status = $paymentMollie->status ?? 'unknown';

        // Order payments should return to the shop flow with explicit user feedback.
        if ($payment->orders()->exists()) {
            if ($status === PaymentStatus::paid->value) {
                return redirect()->route('product.index')->with('success', 'Betaling gelukt! Je bestelling is ontvangen.');
            }

            if (in_array($status, [PaymentStatus::failed->value, PaymentStatus::canceled->value, PaymentStatus::expired->value], true)) {
                return redirect()->route('product.index')->with('error', 'Betaling niet afgerond. Probeer het opnieuw.');
            }

            return redirect()->route('product.index')->with('error', 'Betaling is nog niet afgerond. Controleer de status later opnieuw.');
        }

        // Default flow for non-order payments.
        return redirect()->route('home')->with('payment_status', $status);
    }

    public function webhook(Request $request)
    {
        // Mollie sends the payment ID in the webhook request.
        $paymentId = $request->input('id');

        // If the payment ID is missing, the request is not valid.
        if (!$paymentId) {
            return response('Bad Request', 400);
        }

        // Find the local payment record that belongs to this Mollie payment.
        $paymentDB = Payment::where('mollieId', $paymentId)->first();

        // If we do not know this payment, return 404 so Mollie can retry later.
        if (!$paymentDB) {
            return response('Not Found', 404);
        }

        // Get the latest payment status from Mollie.
        // This can throw an exception if Mollie no longer knows the payment.
        try {
            // Use the correct Mollie endpoint depending on the payment type.
            if ($paymentDB->isPaymentLink) {
                $payments = Mollie::api()->paymentLinkPayments->iteratorForId($paymentDB->mollieId)->all();
                $payment = null;

                // Again, prefer the first payment that is marked as paid.
                foreach ($payments as $p) {
                    if ($p->status === 'paid') {
                        $payment = $p;
                        break;
                    }
                }

                // If nothing is paid yet, fall back to the first payment in the list.
                if (!isset($payment)) {
//                    count($payments) === 0 ? $payment = null : $payment = $payments[0];
                    $payment = $payments[0] ?? null;
                }
            } else {
                $payment = Mollie::api()->payments->get($paymentId);
            }
        } catch (\Mollie\Api\Exceptions\ApiException $e) {
            Log::debug("[PaymentController]Webhook TryCatch: Mollie API exception: {$e->getMessage()}");
            return response('Not Found', 404);
        }

        if (!$payment) {
            return response('OK');
        }

        $latestStatus = $payment->status;

        DB::transaction(function () use ($paymentDB, $payment, $latestStatus) {
            $lockedPayment = Payment::query()->whereKey($paymentDB->id)->lockForUpdate()->firstOrFail();

            if ($lockedPayment->status?->value === $latestStatus) {
                return;
            }

            $lockedPayment->status = $latestStatus;

            if ($payment->isPaid() && !$lockedPayment->paidAt) {
                $lockedPayment->paidAt = $payment->paidAt ?? now();
            }

            $lockedPayment->save();
            $shopApplication = $lockedPayment->application?->shop_order_id ? $lockedPayment->application : null;
            $shopApplication?->handleNewStatus($lockedPayment);
            $lockedPayment->orders()->each(fn ($order) => $order->handleNewStatus($lockedPayment));
            if (!$shopApplication) {
                $lockedPayment->application()->each(fn ($application) => $application->handleNewStatus($lockedPayment));
            }
        });

        // Log the webhook so it is easier to debug when Mollie sends something unexpected.
        Log::debug("[PaymentController] Webhook: Mollie Webhook received for payment ID: {$paymentId}, status: {$latestStatus}");

        // Return 200 so Mollie knows the webhook was received correctly.
        return response('OK');
    }
}
