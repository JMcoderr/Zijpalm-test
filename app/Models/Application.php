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

    /**
     * Calculate the total cost of the application based on activity price and answers to questions.
     *
     * @return float The total cost of the application.
     */
    public function calculateTotalCost(): float
    {
        // Calculate the total cost based on the activity and number of participants
        $activity = $this->activity;

        // Calculate the total cost based on the activity price and number of participants
        $totalCost = $activity->price * $this->participants;

        // Calculate additional costs based on answers to questions
        // For each question, add the costs to the total
        foreach($this->answers as $answer){
            $question = $answer->question;

            // Continue if question actually has a price
            if($question->price || $question->type === 'select'){

                // Numbers are simply multiply by values
                if($question->type === QuestionType::Number){
                    $totalCost += $question->price * ((int)$answer->answer ?: 0);
                }

                // Checkbox: answer is "Ja" or "Nee"
                if($question->type === QuestionType::Checkbox){
                    // If the answer is "Ja", add the price, otherwise add 0
                    $answer->answer == 'Ja' ? $totalCost += $question->price : 0;
                }

                // If the question is of type select, check for selected option's price
                if($question->type === QuestionType::Select){
                    $selected = $answer->answer;
                    foreach($question->selectOptions as $option){
                        if($option['option'] == $selected && !empty($option['price'])){
                            $totalCost += $option['price'];
                        }
                    }
                }
            }
        }

        // Return the total cost
        return $totalCost;
    }
}
