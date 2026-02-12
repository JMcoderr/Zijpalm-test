<?php

namespace App\Http\Controllers;

use App\Mail\ActivityApplied;
use App\Mail\ApplicationCancelled;
use App\Models\Application;
use App\Models\Activity;
use App\Models\Answer;
use App\Models\Guest;
use App\Models\Payment;
use App\ApplicationStatus;
use App\PaymentStatus;
use Illuminate\Http\Request;
use App\Http\Requests\StoreApplicationRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Mollie\Laravel\Facades\Mollie;

class ApplicationController extends Controller
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
    public function store(StoreApplicationRequest $request, Activity $activity)
    {
        // Send the user back if they've already applied, shouldn't be possible, but just in case
        if($activity->userApplied()){
            return redirect()->back()->with('error', 'U heeft zich al ingeschreven voor deze activiteit.');
        }

        // Assign valid guests to a variable
        $guests = collect($request->input('guests', []))->filter(fn($guest)=>collect($guest)->filter()->isNotEmpty());

        // Count participants, 1 is the user, and we add all the non-empty guests
        $participants = 1 + $guests->count();

        // Determine the application status based on capacity and whether the activity has any cost
        if($activity->maxParticipants > 0 && (($activity->participants->all->count() + $participants) > $activity->maxParticipants)){
            // No more spots available, place on reserve list regardless of price
            $status = ApplicationStatus::Reserve;
        }
        elseif(!$activity->hasAnyCost()){
            // Activity is entirely free (no base price, no question prices, no option prices)
            $status = ApplicationStatus::Active;
        } else {
            // Activity has a cost somewhere, set to pending until payment is completed
            $status = ApplicationStatus::Pending;
        }

        // Check if the user is already signed up for this activity
        if(Application::where('activity_id', $activity->id)->where('user_id', auth()->user()->id)->where('status', '!=', ApplicationStatus::Cancelled)->exists()){
            return redirect()->route('activity.show', $activity)->with('error', 'U heeft zich al ingeschreven voor deze activiteit.');
        }

        $totalCost = (float) $activity->price;

        // Create the application
        $application = Application::create([
            'activity_id' => $activity->id,
            'user_id' => auth()->user()->id,
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'participants' => $participants,
            'comment' => $request->input('comment'),
            'status' => $status,
        ]);

        // For each question, add the costs to the total for payment later, and add answers to every question
        foreach($activity->questions as $question){

            // Continue if question actually has a price
            if($question->price || $question->type->value === 'select'){

                // Numbers and checkbox simply multiply by value (checkbox gives a 1 or 0 with (int))
                if($question->type->value === 'number' || $question->type->value === 'checkbox'){
                    $totalCost += $question->price * (is_numeric($request->input("questions.$question->id")) ? (int)$request->input("questions.$question->id") : (int)(bool)$request->input("questions.$question->id"));
                }

                // If the question is of type select, check for selected option's price
                if($question->type->value === 'select' && ($selected = $request->input("questions.$question->id"))){
                    foreach($question->selectOptions as $option){
                        if($option['option'] == $selected && !empty($option['price'])){
                            $totalCost += $option['price'];
                        }
                    }
                }
            }

            // Makes sure all questions have answers, provides Yes or No for checkbox input, gives fallback of No for any other input
            $answer = $question->type->value === 'checkbox' ? ($request->input("questions.$question->id") ? 'Ja' : 'Nee') : $request->input("questions.$question->id", 'Nee');

            // Create answers for every question in the application
            $application->answers()->create([
                'question_id' => $question->id,
                'answer' => $answer,
            ]);
        }

        // Create guests and bind them to application
        if($participants > 1){
            foreach($guests as $guestData){
                $application->guests()->create([
                    'firstName' => $guestData['firstName'],
                    'lastName' => $guestData['lastName'],
                    'email' => $guestData['email'],
                    'phone' => $guestData['phone'],
                    'adult' => array_key_exists('adult', $guestData) && (bool)$guestData['adult'] //$guestData['adult'] ? true : false, //TODO GUEST ERROR: null
                ]);

                // Add the price of the guest to the total cost
                $totalCost += $activity->price;
            }
        }

        $application->activity->updateApplications();

        if($status === ApplicationStatus::Reserve) {
            // Reserve: send confirmation email and redirect back
            Mail::to(config('mail.bestuur.address'), config('mail.bestuur.name'))->send(new ActivityApplied($activity, $application->user, true));
            return redirect()->route('activity.show', $activity)->with('success', "U bent succesvol ingeschreven als reserve voor '{$activity->title}'");
        } elseif($totalCost > 0.0){
            // There is a cost to pay: create iDEAL payment via Mollie and redirect to checkout
            return redirect(Mollie::api()->payments->get(Payment::generatePayment((float) $totalCost, "Inschrijving van {$application->user->name} voor '{$activity->title}'", $application->id)->mollieId)->_links->checkout->href, 303);
        } else {
            // Entirely free (or no paid options selected): activate immediately without payment
            $application->update(['status' => ApplicationStatus::Active]);
            Mail::to(config('mail.bestuur.address'), config('mail.bestuur.name'))->send(new ActivityApplied($activity, $request->user()));
            return redirect()->route('activity.show', $activity)->with('success', "U bent succesvol ingeschreven voor '{$activity->title}'");
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Application $application)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Application $application)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Application $application)
    {
        //
    }

    /**
     * Softly delete the specified resource from storage.
     */
    public function destroy(Application $application)
    {
        // Check if the cancellation period has passed.
        if( now()->gt(Carbon::parse($application->activity->cancellationEnd))){
            return redirect()->back()->with('error', 'The afmeldingperiode is verstreken. Neem contact op met het bestuur met verdere vragen.');
        }

        $application->update(['status' => ApplicationStatus::Cancelled]);
        $application->activity->updateApplications();

        // Send the confirmation email
        Mail::to(config('mail.bestuur.address'), config('mail.bestuur.name'))->send(new ApplicationCancelled($application));

        // Refund the payment(s)
        $application->payments()->each(function(Payment $payment) {
            // If the payment has not been refunded, refund it
            if(!$payment->refunded && $payment->status == PaymentStatus::paid){
                $payment->refund();
            }
        });

        return redirect()->back()->with('success', 'U heeft zich succesvol afgemeld voor deze activiteit.');

        // If an application is removed, cancel it and generate a payment link for the first back-up application
        // https://docs.mollie.com/reference/create-payment-link
    }
}
