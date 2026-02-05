<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use App\PaymentStatus;
use Illuminate\Support\Facades\Log;
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
        // Get the order from the database
        $payment = Payment::findOrFail($paymentId);

        // Check if the payment is a payment link or a regular payment
            // If it is a payment link, use the payment links API, otherwise use the payments API
        if ($payment->isPaymentLink) {
            $payments = Mollie::api()->paymentLinkPayments->iteratorForId($payment->mollieId)->all();
            // Check every payment for the paid status
            foreach ($payments as $p) {
                if ($p->status === 'paid') {
                    $paymentMollie = $p;
                    break;
                }
            }
            // If there is no paid payment, get the first payment or set it to null
            if (!isset($paymentMollie)) {
                count($payments) === 0 ? $paymentMollie = null : $paymentMollie = $payments[0];
            }
        } else {
            $paymentMollie = Mollie::api()->payments->get($payment->mollieId);
        }

        // Redirect to the home page with the payment status
        return redirect()->route('home')->with('payment_status', $paymentMollie->status ?? "unknown");
    }

    public function webhook(Request $request)
    {
        // Get the payment ID from the request
        $paymentId = $request->input('id');

        // If the payment ID is not set, return 400
        if (!$paymentId) {
            return response('Bad Request', 400);
        }

        // Get the payment from the database
        $paymentDB = Payment::where('mollieId', $paymentId)->first();

        // If the payment does not exist, return 404 because the payment is not in the database
        if (!$paymentDB) {
            return response('Not Found', 404);
        }

        // Get the payment from Mollie
        // This will throw an exception if the payment does not exist
        try {
            // Check if the payment is a payment link or a regular payment
            // If it is a payment link, use the payment links API, otherwise use the payments API
            if ($paymentDB->isPaymentLink) {
                $payments = Mollie::api()->paymentLinkPayments->iteratorForId($paymentDB->mollieId)->all();
                $payment = null;

                // Check every payment for the paid status
                foreach ($payments as $p) {
                    if ($p->status === 'paid') {
                        $payment = $p;
                        break;
                    }
                }
                // If there is no paid payment, get the first payment or set it to null
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

//        // If the payment has not changed, return 200
//        if ($paymentDB->status == $payment->status) {
//            return response('OK');
//        } else { // If the payment has changed, update the payment status
//            $paymentDB->status = $payment->status;
//
//            // If the payment is paid, set the paidAt date
//            if ($payment->isPaid() && !$paymentDB->paidAt) {
//                $paymentDB->paidAt = $payment->paidAt ?? now();
//            }
//            $paymentDB->save();
//
//            // Handle status update for related entities
//            $paymentDB->orders()->each(function ($order) use ($paymentDB) {
//                $order->handleNewStatus($paymentDB);
//            });
//
//            $paymentDB->application()->each(function ($application) use ($paymentDB) {
//                $application->handleNewStatus($paymentDB);
//            });
//        }
        // If the payment has not changed, return 200
        if ($paymentDB->status == $payment->status) {
            return response('OK');
        }

        // If the payment has changed, update the payment status
        $paymentDB->status = $payment->status;

        // If the payment is paid, set the paidAt date
        if ($payment->isPaid() && !$paymentDB->paidAt) {
            $paymentDB->paidAt = $payment->paidAt ?? now();
        }

//        // Set refundedAt only once using Mollie's timestamp
//        if($payment->isRefunded() && !$paymentDB->refundedAt) {
//            $paymentDB->refundedAt = $payment->refundedAt ?? now();
//        }

        $paymentDB->save();

        // Handle status update for related entities
        $paymentDB->orders()->each(function ($order) use ($paymentDB) {
            $order->handleNewStatus($paymentDB);
        });

        $paymentDB->application()->each(function ($application) use ($paymentDB) {
            Log::debug("[PaymentController]Mollie Webhook received for payment of application\n".
                json_encode([
                    'application' => $application,
                    'paymentDb' => $paymentDB,
                    'this' => $this,
            ], JSON_PRETTY_PRINT));
//            Log::debug("[PaymentController]Mollie Webhook received for payment of application\n",
//                [
//                    'application' => $application,
//                    'paymentDb' => $paymentDB,
//                    'this' => $this,
//                ]);
            $application->handleNewStatus($paymentDB);
        });

        // Log the payment status
        Log::debug("[PaymentController] Webhook: Mollie Webhook received for payment ID: {$paymentId}, status: {$payment->status}");

        // Return 200
        // Mollie will retry the webhook if it doesn't receive a 200 response
        return response('OK');
    }
}
