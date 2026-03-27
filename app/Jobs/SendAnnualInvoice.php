<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Mollie\Laravel\Facades\Mollie;

class SendAnnualInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 120;

    public function __construct(
        public int $userId,
        public float $amount,
        public int $year,
    ) {
    }

    public function handle(): void
    {
        $user = User::query()->find($this->userId);

        if (!$user || !$user->email) {
            return;
        }

        $description = "Jaarlijkse contributie {$this->year} - {$user->name}";

        $payment = Payment::generatePaymentLink(
            $this->amount,
            $description,
            now()->addMonth()
        );

        $paymentLink = Mollie::api()->paymentLinks->get($payment->mollieId)->_links->checkout->href;

        Mail::raw(
            "Beste {$user->name},\n\n" .
            "Hierbij ontvang je de jaarlijkse factuur voor {$this->year}.\n" .
            "Bedrag: " . formatPrice($this->amount) . "\n\n" .
            "Betaal via deze link:\n{$paymentLink}\n\n" .
            "Met vriendelijke groet,\nZijpalm",
            function ($message) use ($user) {
                $message->to($user->email, $user->name)
                    ->cc('Zijpalm@almere.nl', 'Zijpalm')
                    ->subject("Jaarlijkse factuur {$this->year} - Zijpalm");
            }
        );
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[SendAnnualInvoice] Job failed', [
            'user_id' => $this->userId,
            'amount' => $this->amount,
            'year' => $this->year,
            'error' => $exception->getMessage(),
        ]);
    }
}
