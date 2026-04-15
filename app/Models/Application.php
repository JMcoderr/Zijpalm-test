<?php

namespace App\Models;

use App\ApplicationStatus;
use App\Mail\ActivityApplied;
use App\Mail\PaymentFailed;
use App\PaymentStatus;
use App\Models\User;
use App\QuestionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class Application extends Model
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'activity_id',
        'email',
        'phone',
        'comment',
        'status',
        'participants',
    ];

    protected $casts = [
        'status' => ApplicationStatus::class,
        'participants' => 'integer',
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function guests()
    {
        return $this->hasMany(Guest::class);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    // This function is called when the payment status changes
    public function handleNewStatus(Payment $payment) {
        // Set application status to active and send confirmation email to the user on succesful payment
        if($payment->status === PaymentStatus::paid && $this->status === ApplicationStatus::Pending) {
            $this->status = ApplicationStatus::Active;
            Mail::to(config('mail.bestuur.address'), config('mail.bestuur.name'))->send(new ActivityApplied($this->activity, $this->user));
        } else if($payment->status === PaymentStatus::paid && $this->status === ApplicationStatus::Cancelled) { // DON'T TOUCH, Added to catch an edge case where payment_failed emails got triggered from a unknown webhook trigger.
            Log::debug("[ApplicationModel] handleNewStatus: Mollie Webhook received, application status was cancelled while payment status was paid");
        } else { // If the payment failed, set the application status to cancelled and send a payment failed email
            Log::debug("[ApplicationModel] handleNewStatus: Mollie Webhook received for payment", [
                'payment' => $payment,
                'status' => $this->status
            ]);
            $this->status = ApplicationStatus::Cancelled;
            Mail::to(config('mail.bestuur.address'), config('mail.bestuur.name'))->send(new PaymentFailed($payment, $this->user));
        }

        // Update the applications
        $this->activity->updateApplications();

        $this->save();
    }

    public function isOrganizerApplication(): bool
    {
        return (bool) ($this->activity?->organizer && $this->user?->name && str_contains($this->activity->organizer, $this->user->name));
    }

    public function isFreeOrganizerApplication(): bool
    {
        $activity = $this->activity;

        if (!$activity || !$this->isOrganizerApplication() || (int) ($activity->free_organizer_count ?? 0) <= 0) {
            return false;
        }

        $freeOrganizerApplications = $activity->applications
            ->whereNotIn('status', [ApplicationStatus::Cancelled, ApplicationStatus::Reserve, ApplicationStatus::Pending])
            ->sortBy('created_at')
            ->filter(fn ($application) => $application->isOrganizerApplication())
            ->take((int) $activity->free_organizer_count);

        return $freeOrganizerApplications->contains('id', $this->id);
    }

    public function calculateBaseCost(): float
    {
        $activity = $this->activity;

        if (!$activity) {
            return 0.0;
        }

        if ($this->isFreeOrganizerApplication()) {
            return max(0, $this->participants - 1) * (float) $activity->price;
        }

        return $this->participants * (float) $activity->price;
    }

    public function calculateExtrasCost(): float
    {
        return (float) $this->answers->sum(fn ($answer) => getAnswerPrice($answer));
    }

    public function calculateTotalPaid(): float
    {
        return (float) $this->payments
            ->where('status', PaymentStatus::paid)
            ->sum(fn ($payment) => (float) $payment->getPrice());
    }

    /**
     * Calculate the total cost of the application based on activity price and answers to questions.
     *
     * @return float The total cost of the application.
     */
    public function calculateTotalCost(): float
    {
        return $this->calculateBaseCost() + $this->calculateExtrasCost();
    }
}
