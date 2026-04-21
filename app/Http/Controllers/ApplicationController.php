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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Mollie\Laravel\Facades\Mollie;
use Throwable;

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

        // Count participants: if user signs up with an intro, count as 2 participants
        $participants = ($guests->count() > 0) ? 2 : 1;

        // Check if the logged-in user is an organizer (name comparison in PHP)
        $isOrganizer = false;
        if (auth()->user() && $activity->organizer) {
            $isOrganizer = str_contains($activity->organizer, auth()->user()->name);
        }

        // Count active free organizers in PHP
        $activeFreeOrganizers = $activity->applications
            ->where('status', ApplicationStatus::Active)
            ->filter(function($app) use ($activity) {
                return str_contains($activity->organizer, $app->user->name);
            })->count();

        // Determine if this user can register for free
        $canRegisterFree = $isOrganizer && ($activeFreeOrganizers < $activity->free_organizer_count);

        // Determine the application status based on capacity and cost
        if($activity->maxParticipants > 0 && (($activity->participants->all->count() + $participants) > $activity->maxParticipants)){
            // No more spots available, place on reserve list regardless of price
            $status = ApplicationStatus::Reserve;
        }
        elseif($canRegisterFree){
            // Organizer can register for free
            $status = ApplicationStatus::Active;
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

        // Compute base cost once to avoid accidental double counting.
        $baseCost = $canRegisterFree
            ? ($guests->count() * (float) $activity->price)
            : ($participants * (float) $activity->price);

        // Collect option/question costs separately, then combine once at the end.
        $optionsCost = 0.0;

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

        // For each question, calculate option costs and store answers
        foreach($activity->questions as $question){
            $inputValue = $this->questionInput($request, (string) $question->id);

            if($question->type->value === 'number' && $question->price){
                $amount = is_numeric($inputValue) ? (int) $inputValue : 0;
                $optionsCost += (float) $question->price * $amount;
            }

            if($question->type->value === 'checkbox' && $question->price){
                $optionsCost += $inputValue ? (float) $question->price : 0.0;
            }

            if($question->type->value === 'select' && $inputValue){
                $selectedOption = $question->selectOptions->firstWhere('option', $inputValue);
                if($selectedOption && !empty($selectedOption->price)){
                    $optionsCost += (float) $selectedOption->price;
                }
            }

            // Makes sure all questions have answers, provides Yes or No for checkbox input, gives fallback of No for any other input
            $answer = $question->type->value === 'checkbox'
                ? ($inputValue ? 'Ja' : 'Nee')
                : ($inputValue ?? 'Nee');

            // Create answers for every question in the application
            $application->answers()->create([
                'question_id' => $question->id,
                'answer' => $answer,
            ]);
        }

        $totalCost = $baseCost + $optionsCost;

        Log::debug('[ApplicationController] Payment breakdown', [
            'application_id' => $application->id,
            'activity_id' => $activity->id,
            'participants' => $participants,
            'guest_count' => $guests->count(),
            'base_cost' => $baseCost,
            'options_cost' => $optionsCost,
            'total_cost' => $totalCost,
            'can_register_free' => $canRegisterFree,
        ]);

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
            }
        }

        $application->activity->updateApplications();

        if($status === ApplicationStatus::Reserve) {
            // Reserve: confirmation mail and redirect
            $this->sendBoardNotification(new ActivityApplied($activity, $application->user, true), 'reserve_signup');
            return redirect()->route('activity.show', $activity)->with('success', "U bent succesvol ingeschreven als reserve voor '{$activity->title}'");
        }

        // Organizer free registration: always activate and never redirect to Mollie, except if the organizer brings a guest
        if($canRegisterFree && $guests->count() == 0) {
            $application->update(['status' => ApplicationStatus::Active]);
            $this->sendBoardNotification(new ActivityApplied($activity, $request->user()), 'free_organizer_signup');
            return redirect()->route('activity.show', $activity)->with('success', "Je bent succesvol en gratis als organisator aangemeld voor '{$activity->title}'.");
        }

        if($totalCost > 0.0){
            // Payment required: create Mollie payment and redirect
            return redirect(Mollie::api()->payments->get(Payment::generatePayment((float) $totalCost, "Inschrijving van {$application->user->name} voor '{$activity->title}'", $application->id)->mollieId)->_links->checkout->href, 303);
        } else {
            // Entirely free (or no paid options selected): activate immediately without payment
            $application->update(['status' => ApplicationStatus::Active]);
            $this->sendBoardNotification(new ActivityApplied($activity, $request->user()), 'free_signup');
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
     * Admins can always cancel applications. Participants can only cancel within the cancellation period.
     */
    public function destroy(Application $application, bool $adminOverride = false)
    {
        // Allow admins to bypass the cancellation period check.
        // Regular participants can only cancel if a cancellation date is set and we are still within that period.
        if (!$adminOverride && !auth()->user()?->isAdmin()) {
            // If no cancellation date is set (checkbox "Kosteloos annuleren is niet mogelijk" is checked), block cancellation.
            if (!$application->activity->cancellationEnd) {
                return redirect()->back()->with('error', 'Annuleren is niet mogelijk voor deze activiteit. Neem contact op met het bestuur met verdere vragen.');
            }

            // If the cancellation period has passed, block cancellation.
            if (now()->gt(Carbon::parse($application->activity->cancellationEnd)->endOfDay())) {
                return redirect()->back()->with('error', 'De afmeldperiode is verstreken. Neem contact op met het bestuur met verdere vragen.');
            }
        }

        $application->update(['status' => ApplicationStatus::Cancelled]);
        $application->activity->updateApplications();

        // Send the confirmation email
        $this->sendBoardNotification(new ApplicationCancelled($application), 'application_cancelled');

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

    /**
     * Send board notifications without breaking signup/cancel flows when mail transport is unavailable.
     */
    private function sendBoardNotification($mailable, string $context): void
    {
        $address = config('mail.bestuur.address');
        $name = config('mail.bestuur.name');

        if (blank($address)) {
            Log::warning('[ApplicationController] Board mail skipped: address missing', [
                'context' => $context,
            ]);

            return;
        }

        try {
            Mail::to($address, $name)->send($mailable);
        } catch (Throwable $exception) {
            Log::error('[ApplicationController] Board mail send failed', [
                'context' => $context,
                'address' => $address,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Read question input from both supported payload formats.
     */
    private function questionInput(Request $request, string $questionId): mixed
    {
        $nestedValue = $request->input("questions.$questionId");

        // Support structures like questions[123][value]
        if (is_array($nestedValue) && array_key_exists('value', $nestedValue)) {
            return $nestedValue['value'];
        }

        if (!is_null($nestedValue)) {
            return $nestedValue;
        }

        $nestedValueField = $request->input("questions.$questionId.value");
        if (!is_null($nestedValueField)) {
            return $nestedValueField;
        }

        $flatValue = $request->input($questionId);
        if (!is_null($flatValue)) {
            return $flatValue;
        }

        // Fallback for indexed payloads where each question object carries an id and value.
        foreach ((array) $request->input('questions', []) as $item) {
            if (is_array($item) && isset($item['id']) && (string) $item['id'] === $questionId) {
                return $item['value'] ?? null;
            }
        }

        return null;
    }
}
