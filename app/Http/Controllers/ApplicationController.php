<?php
// This file is part of the app logic and has a short comment so it is easier to read.


namespace App\Http\Controllers;

use App\Mail\ActivityApplied;
use App\Mail\ApplicationCancelled;
use App\Models\Application;
use App\Models\Activity;
use App\Models\Answer;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\User;
use App\ApplicationStatus;
use App\PaymentStatus;
use Illuminate\Http\Request;
use App\Http\Requests\StoreApplicationRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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

        // Count the user plus all valid introducees
        $participants = max(1, $guests->count() + 1);

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
        if($activity->maxParticipants > 0 && $activity->participants->capacity < $participants){
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
     * Let an admin add a member to the participant list by hand, no matter if they paid or not.
     */
    public function adminStore(Request $request, Activity $activity)
    {
        $data = $request->validateWithBag('addParticipant', [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'participants' => ['required', 'integer', 'min:1', 'max:50'],
            'status' => ['required', 'in:' . ApplicationStatus::Active->value . ',' . ApplicationStatus::Pending->value . ',' . ApplicationStatus::Reserve->value],
            'comment' => ['nullable', 'string', 'max:500'],
        ], [
            'user_id.required' => 'Kies eerst een lid door in stap 1 op een naam te klikken.',
            'user_id.integer' => 'Kies eerst een lid door in stap 1 op een naam te klikken.',
            'user_id.exists' => 'Dit lid bestaat niet meer.',
            'participants.required' => 'Vul in met hoeveel personen dit lid komt.',
            'participants.integer' => 'Vul bij het aantal personen een heel getal in.',
            'participants.min' => 'Het aantal personen moet minimaal 1 zijn.',
            'participants.max' => 'Je kunt maximaal 50 personen tegelijk toevoegen.',
            'status.required' => 'Kies in stap 3 hoe dit lid op de lijst komt.',
            'status.in' => 'Kies in stap 3 een van de drie mogelijkheden.',
            'comment.max' => 'De opmerking mag maximaal 500 tekens lang zijn.',
        ]);

        $user = User::withTrashed()->find($data['user_id']);

        if (!$user) {
            return redirect()->back()->with('error', 'Dit lid bestaat niet meer.');
        }

        // Someone can only be on the list once, cancelled sign-ups do not count.
        $alreadyApplied = Application::where('activity_id', $activity->id)
            ->where('user_id', $user->id)
            ->where('status', '!=', ApplicationStatus::Cancelled)
            ->exists();

        if ($alreadyApplied) {
            return redirect()->back()->with('error', "{$user->name} staat al op de deelnemerslijst.");
        }

        $status = ApplicationStatus::from($data['status']);

        try {
            // Empty profile contact details must still satisfy the applications table.
            $application = Application::create([
                'activity_id' => $activity->id,
                'user_id' => $user->id,
                'email' => $user->email ?? '',
                'phone' => $user->phone ?? '',
                'participants' => (int) $data['participants'],
                'comment' => filled($data['comment'] ?? null) ? $data['comment'] : 'Handmatig toegevoegd door een beheerder.',
                'status' => $status,
            ]);

            // Give every question a default answer so exports and overviews keep working.
            foreach ($activity->questions as $question) {
                $application->answers()->create([
                    'question_id' => $question->id,
                    'answer' => 'Nee',
                ]);
            }
        } catch (Throwable $exception) {
            Log::error('[ApplicationController] Manually adding a participant failed', [
                'activity_id' => $activity->id,
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);

            return redirect()->back()->with('error', "{$user->name} kon niet worden toegevoegd. Controleer of het profiel van dit lid compleet is en probeer het opnieuw.");
        }

        $activity->updateApplications();

        return redirect()->back()->with('success', "{$user->name} is toegevoegd aan de deelnemerslijst.");
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

    public function adminUpdateStatuses(Request $request, Activity $activity)
    {
        $data = $request->validateWithBag('editApplications', [
            'applications' => ['required', 'array', 'min:1'],
            'applications.*.id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('applications', 'id')->where(fn ($query) => $query
                    ->where('activity_id', $activity->id)
                    ->where('status', '!=', ApplicationStatus::Cancelled->value)),
            ],
            'applications.*.status' => ['required', Rule::in([
                ApplicationStatus::Active->value,
                ApplicationStatus::Pending->value,
                ApplicationStatus::Reserve->value,
            ])],
            'applications.*.previous_status' => ['required', Rule::in([
                ApplicationStatus::Active->value,
                ApplicationStatus::Pending->value,
                ApplicationStatus::Reserve->value,
            ])],
        ], [
            'applications.required' => 'Er zijn geen aanmeldingen om te wijzigen.',
            'applications.*.id.exists' => 'Een aanmelding is niet meer beschikbaar voor deze activiteit. Vernieuw de pagina.',
            'applications.*.id.distinct' => 'Een aanmelding mag maar eenmaal worden gewijzigd.',
            'applications.*.status.required' => 'Kies voor iedere aanmelding een status.',
            'applications.*.status.in' => 'Kies Aangemeld, Reserve of In afwachting van betaling.',
            'applications.*.previous_status.required' => 'De oorspronkelijke status ontbreekt. Vernieuw de pagina.',
            'applications.*.previous_status.in' => 'De oorspronkelijke status is ongeldig. Vernieuw de pagina.',
        ]);

        $changedCount = DB::transaction(function () use ($data, $activity) {
            $applications = $activity->applications()
                ->whereIn('id', array_column($data['applications'], 'id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $changedCount = 0;

            foreach ($data['applications'] as $entry) {
                $application = $applications->get($entry['id']);

                if (!$application || $application->status === ApplicationStatus::Cancelled) {
                    throw ValidationException::withMessages([
                        'applications' => 'Een aanmelding is ondertussen verwijderd of geannuleerd. Vernieuw de pagina.',
                    ])->errorBag('editApplications');
                }

                if ($application->status->value !== $entry['previous_status']) {
                    throw ValidationException::withMessages([
                        'applications' => 'Een aanmelding is ondertussen gewijzigd, bijvoorbeeld door een betaling of een andere beheerder. Vernieuw de pagina en probeer opnieuw.',
                    ])->errorBag('editApplications');
                }

                $status = ApplicationStatus::from($entry['status']);

                if ($application->status !== $status) {
                    $previousStatus = $application->status;
                    $application->update(['status' => $status]);
                    Log::info('[ApplicationController] Admin corrected application status', [
                        'admin_id' => auth()->id(),
                        'activity_id' => $activity->id,
                        'application_id' => $application->id,
                        'previous_status' => $previousStatus->value,
                        'status' => $status->value,
                    ]);
                    $changedCount++;
                }
            }

            // Corrections must not immediately promote the people just moved to reserve.
            return $changedCount;
        });

        return redirect()->route('activity.show', $activity)->with('success',
            $changedCount === 0 ? 'Er zijn geen statussen gewijzigd.' : "De aanmeldingen zijn bijgewerkt ({$changedCount} gewijzigd).");
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Application $application)
    {
        if (!auth()->user()?->isAdmin()) {
            return redirect()->back()->with('error', 'Je hebt hier geen rechten voor.');
        }

        if ($application->status !== ApplicationStatus::Pending) {
            return redirect()->back()->with('error', 'Alleen aanmeldingen in afwachting van betaling kunnen op betaald worden gezet.');
        }

        $data = $request->validate([
            'status' => ['required', 'in:' . implode(',', ApplicationStatus::toArray())],
        ]);

        if ($data['status'] !== ApplicationStatus::Active->value) {
            return redirect()->back()->with('error', 'Deze actie kan alleen gebruikt worden om een aanmelding op betaald te zetten.');
        }

        $payment = $application->payments()->latest('created_at')->first();

        if ($payment) {
            $payment->status = PaymentStatus::paid;
            $payment->paidAt = $payment->paidAt ?? now();
            $payment->save();
        }

        $application->update(['status' => ApplicationStatus::Active]);
        $application->activity->updateApplications();

        return redirect()->back()->with('success', 'De aanmelding is op betaald gezet.');
    }

    /**
     * Softly delete the specified resource from storage.
     * Admins can always cancel applications. Participants can only cancel within the cancellation period.
     */
    public function destroy(Application $application, bool $adminOverride = false)
    {
        $isAdmin = (bool) auth()->user()?->isAdmin();

        // Allow admins to bypass the cancellation period check.
        // Regular participants can only cancel if a cancellation date is set and we are still within that period.
        if (!$adminOverride && !$isAdmin) {
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

        $isOwnApplication = $application->user_id === auth()->id();
        $participantName = $application->user?->name ?? 'De deelnemer';

        return redirect()->back()->with('success', $isOwnApplication
            ? 'U heeft zich succesvol afgemeld voor deze activiteit.'
            : "{$participantName} is van de deelnemerslijst verwijderd.");

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
