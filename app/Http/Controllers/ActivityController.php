<?php
// This file is part of the app logic and has a short comment so it is easier to read.


namespace App\Http\Controllers;

use App\Http\Requests\NotifyMembersRequest;
use App\Http\Requests\NotifyParticipantsRequest;
use App\Http\Requests\StoreActivityRequest;
use App\Http\Requests\UpdateActivityRequest;
use App\Mail\ActivityReminder;
use App\Mail\NewActivity;
use App\Models\Activity;
use App\ActivityType;
use App\Models\User;
use App\ApplicationStatus;
use App\UserNotifications;
use BumpCore\EditorPhp\EditorPhp;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Laravel\Facades\Image;

class ActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Show all activities
        // $activities = Activity::all();

        // Get ongoing activities that aren't cancelled, sorted by starting date/time
        $activities = Activity::where('end', '>', now())->whereNotIn('type', [ActivityType::Weekly, ActivityType::Cancelled])->get()->sortBy('start');

        // Get all recurring activities, since they are treated separately
        $recurringActivities = Activity::where('type', ActivityType::Weekly)->get()->sortBy('title');

        // Get activities that are past the end date/time, from the last year only, with the latest ones on top, excluding weekly or cancelled activities
        $archivedActivities = Activity::where('end', '<', now())->where('end', '>=', now()->subYear())->whereNotIn('type', [ActivityType::Weekly, ActivityType::Cancelled])->get()->sortBy('end');

        // Return the index with all 3 as compact
        return view('activities.index', compact('activities', 'recurringActivities', 'archivedActivities'));
    }

    /**
     * Display the suggestion form.
     */
    public function suggestion()
    {
        return view('activities.suggestion');
    }

    /**
     * Process the suggestion form.
     */
    public function processSuggestion(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'activity_name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240'], // 10MB per file
        ]);

        // Prepare attachment file paths if any
        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = Storage::disk('local')->put('suggestion_attachments', $file);
                $attachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                ];
            }
        }

        // Send suggestion email to Zijpalm inbox
        try {
            Mail::to('zijpalm@almere.nl', 'Zijpalm')->send(
                new \App\Mail\ActivitySuggestion(
                    $validated['name'],
                    $validated['email'],
                    $validated['activity_name'],
                    $validated['description'],
                    $attachments
                )
            );
        } catch (\Throwable $e) {
            // Log any error (including type errors) but don't break the user flow.
            \Illuminate\Support\Facades\Log::error('Failed to send activity suggestion', [
                'error' => $e->getMessage(),
                'email' => $validated['email'],
            ]);

            return redirect()->route('activity.suggestion')
                ->with('error', 'Er ging iets mis bij het versturen. Probeer het opnieuw of neem contact op met het bestuur.');
        }

        return redirect()->route('activity.suggestion')
            ->with('success', 'Bedankt voor je idee! We hebben het ontvangen en zullen dit binnenkort bekijken.');
    }

    /***
     * Returns a collection of participant email addresses for a given activity.
     * @param Activity $activity
     * @return \Illuminate\Support\Collection
     */
    private function retrieveParticipantEmails(Activity $activity, array $status = []) {
        $emails = collect();
        $applications = $activity->applications()->with(['user', 'guests'])->whereIn('status', $status)->get();

        // Retrieve each participant's email
        if($applications->isNotEmpty()) {
            foreach ($applications as $application){
                $emails->push($application->email);
                $emails = $emails->merge($application->guests->pluck('email'));
            }
        }

        return $emails;
    }

    /**
     *  Validates whether the provided variables could cause a timeout in Power Automate.
     *
     * @param Collection $emails    A collection of recipient emails
     * @param int $batchSize        The amount of people in the BCC per email
     * @param int $delay            The amount of time between sending each email (in seconds)
     * @return array
     */
    private function validationPowerAutomate(Collection $emails, int $batchSize, int $delay): array {
        $errorArray = [];

        // Validate whether there are people to send mails to.
        if($emails->isEmpty() || $emails->count() <= 0) {
            $errorArray['participants'] = 'Er zijn geen leden die actief ingeschreven staan voor deze activiteit.';
        }

        // Validate whether the current recipient count and batch_size would exceed Power Automate's timeout limit.
        if($emails->count()/$batchSize > config('mail.power_automate.send_limit')) {
            $errorArray['batch_size'] = 'Het aantal ontvangers per e-mail is te klein voor het totaal aantal ontvangers.';
        }

        // Validate whether the current delay would result in reaching Power Automate's 1-hour timeout limit.
        if($emails->count()/$batchSize * $delay > 3600) {
            $errorArray['delay'] = 'De wachttijd tussen mails is te hoog voor het aantal mails dat verstuurt gaan worden.';
        }

        return $errorArray;
    }

    /**
     * Normalize and sanitize manually entered finance rows.
     */
    private function sanitizeManualFinanceEntries(?array $entries): array
    {
        if (!is_array($entries)) {
            return [];
        }

        $sanitized = [];

        foreach ($entries as $entry) {
            $description = trim((string) ($entry['description'] ?? ''));
            $quantityRaw = $entry['quantity'] ?? null;
            $unitPriceRaw = $entry['unit_price'] ?? null;

            $hasQuantityInput = $quantityRaw !== null && $quantityRaw !== '';
            $hasUnitPriceInput = $unitPriceRaw !== null && $unitPriceRaw !== '';

            $quantity = $hasQuantityInput ? max(0, (float) str_replace(',', '.', (string) $quantityRaw)) : 0.0;
            $unitPrice = $hasUnitPriceInput ? formatPriceForDb((string) $unitPriceRaw) : 0.0;

            $hasDescription = $description !== '';
            $isCompleteRow = $hasDescription && $hasQuantityInput && $hasUnitPriceInput;

            // Persist only fully completed rows.
            if (!$isCompleteRow) {
                continue;
            }

            $sanitized[] = [
                'description' => $description,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => round($quantity * $unitPrice, 2),
            ];
        }

        return $sanitized;
    }

    private function parseManualBudget(mixed $budget): ?float
    {
        if ($budget === null || $budget === '') {
            return null;
        }

        return formatPriceForDb((string) $budget);
    }

    /***
     * Sends a reminder email to participants registered for a given activity.
     * @param Activity $activity
     * @return \Illuminate\Http\RedirectResponse
     */
    public function notifyParticipants(NotifyParticipantsRequest $request, Activity $activity) {
        // Cast validated integers to ints, required for Power Automate's parsing.
        $validatedData = $request->validated();
        $validatedData = castValidatedInts($validatedData, ['delay', 'batch_size']);

        // Retrieve all emails of registered signups (exclude cancelled).
        $emails = $this->retrieveParticipantEmails($activity, [
            ApplicationStatus::Active,
            ApplicationStatus::Pending,
            ApplicationStatus::Reserve,
        ]);
        $emails = $emails
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values();

        // Check if provided Power Automate variables could cause issues.
        $errorArray = $this->validationPowerAutomate($emails, $validatedData['batch_size'], $validatedData['delay']);

        if(!empty($errorArray)) {
            throw ValidationException::withMessages($errorArray)->errorBag('reminderMail');
        }

            // Persist reminder mail settings so they are shown next time the modal opens.
            try {
                \App\Models\MailSetting::updateOrCreate(
                    ['name' => 'reminder'],
                    ['batch_size' => $validatedData['batch_size'], 'delay' => $validatedData['delay']]
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[ActivityController] could not persist reminder settings', ['error' => $e->getMessage()]);
            }

            // Power Automate Json mail
            Mail::to(config('mail.bestuur.address'), config('mail.bestuur.name'))->send(new ActivityReminder($activity, $emails, $validatedData));

        return redirect()->route('activity.show', $activity)->with('success', 'De herinnering is verstuurt');
    }

    /***
     * Sends an email to members who have not signed up for a given activity to notify them of its availability.
     * @param Activity $activity
     * @return \Illuminate\Http\RedirectResponse
     */
    public function notifyMembers(NotifyMembersRequest $request, Activity $activity) {
        // Cast validated integers to ints, required for Power Automate's parsing.
        $validatedData = $request->validated();
        $validatedData = castValidatedInts($validatedData, ['delay', 'batch_size']);

        // Build the recipient list: all users who opted in for new-activity notifications,
        // excluding those already registered for this activity.
        $participantEmails = $this->retrieveParticipantEmails($activity, [
            ApplicationStatus::Active,
            ApplicationStatus::Pending,
            ApplicationStatus::Reserve,
        ])->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))->unique()->values();

        $allOptedIn = User::query()
            ->notSoftDeleted()
            ->get()
            ->filter(fn ($u) => filter_var($u->email, FILTER_VALIDATE_EMAIL))
            ->pluck('email')
            ->unique()
            ->values();

        $nonParticipantEmails = $allOptedIn->diff($participantEmails)->values();

        // Check if provided Power Automate variables could cause issues.
        $errorArray = $this->validationPowerAutomate($nonParticipantEmails, $validatedData['batch_size'], $validatedData['delay']);

        if(!empty($errorArray)) {
            throw ValidationException::withMessages($errorArray)->errorBag('announcementMail');
        }

        // Persist the chosen announcement mail settings so they are shown next time the modal opens.
        try {
            \App\Models\MailSetting::updateOrCreate(
                ['name' => 'announcement'],
                ['batch_size' => $validatedData['batch_size'], 'delay' => $validatedData['delay']]
            );
        } catch (\Throwable $e) {
            // Log but don't block sending the mail
            \Illuminate\Support\Facades\Log::warning('[ActivityController] could not persist announcement settings', ['error' => $e->getMessage()]);
        }

        // Power Automate json mail
        Mail::to(config('mail.bestuur.address'), config('mail.bestuur.name'))->send(
            new NewActivity($activity, $nonParticipantEmails, [
                'batch_size' => $validatedData['batch_size'],
                'delay' => $validatedData['delay'],
            ])
        );

        return redirect()->route('activity.show', $activity)->with('success', 'De aankondiging is verstuurt');
    }

    /**
     * Manually send the upcoming activities digest from the activities index.
     */
    public function sendUpcomingActivitiesDigest(Request $request)
    {
        $validated = $request->validate([
            'batch_size' => 'required|integer|between:' . config('mail.power_automate.batch_size.min') . ',' . config('mail.power_automate.batch_size.max'),
            'delay' => 'required|integer|between:' . config('mail.power_automate.delay.min') . ',' . config('mail.power_automate.delay.max'),
            'activity_ids' => 'nullable|array',
            'activity_ids.*' => 'integer|exists:activities,id',
        ]);

        // Persist digest settings so user input is remembered across modal opens
        try {
            \App\Models\MailSetting::updateOrCreate(
                ['name' => 'digest'],
                ['batch_size' => $validated['batch_size'], 'delay' => $validated['delay']]
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[ActivityController] could not persist digest settings', ['error' => $e->getMessage()]);
        }

        $exitCode = Artisan::call('app:send-upcoming-activities-digest', [
            '--batch_size' => (int) $validated['batch_size'],
            '--delay' => (int) $validated['delay'],
            '--activity_ids' => array_values(array_unique(array_map('intval', $validated['activity_ids'] ?? []))),
        ]);

        if ($exitCode !== 0) {
            return redirect()->route('activity.index')
                ->with('error', 'Mail toekomstige activiteiten kon niet worden verstuurd. Controleer de logs.');
        }

        return redirect()->route('activity.index')
            ->with('success', trim(Artisan::output()) ?: 'Mail toekomstige activiteiten is verstuurd.');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('activities.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreActivityRequest $request){
        // Store all the request data in a temp variable
        $data = $request->all();
        $manualFinanceEntries = $this->sanitizeManualFinanceEntries($data['manual_finance_entries'] ?? null);
        $manualBudget = $this->parseManualBudget($data['manual_budget'] ?? null);

        // Check if an activity is recurring, if not a start-date is required.
        if(!isset($data['start-date']) && !isset($data['recurring'])) {
            return back()->withErrors(['start-date' => 'Een activiteit heeft een startdatum nodig.']);
        }

        if(!isset($data['recurring'])) {
            // If either the end date or end time are missing
            if(!isset($data['end-date']) || !isset($data['end-time'])){
                // If there is no end date, set to same as start date (start is always required)
                if(!isset($data['end-date']) && isset($data['start-date'])){
                    $data['end-date'] = $data['start-date'];
                }

                // If there's no end time, set to a minute before midnight
                if(!isset($data['end-time'])){
                    $data['end-time'] = '23:59';
                }
            }
        }

        // Default type
        $type = ActivityType::OneDay;
        // Set type to Weekly if the activity is recurring
        if(isset($data['recurring'])){
            $type = ActivityType::Weekly;
        }
        // Set type to MultiDay if the start and end date are different
        else if($data['start-date'] !== $data['end-date']){
            $type = ActivityType::MultiDay;
        }

        // Set start and end values.
        if ($type === ActivityType::Weekly) {
            $weekday = isset($data['recurring_weekday']) ? (int) $data['recurring_weekday'] : null;
            $baseDate = $weekday ? now()->copy()->setISODate((int) now()->isoWeekYear(), (int) now()->isoWeek(), $weekday) : null;
            $start = $baseDate ? $baseDate->copy()->startOfDay() : null;
            $end = $baseDate ? $baseDate->copy()->endOfDay() : null;
        } else {
            $start = isset($data['start-date']) ? $data['start-date'] . ' ' . $data['start-time'] : null;
            $end = isset($data['end-date']) ? $data['end-date'] . ' ' . $data['end-time'] : null;
        }

        // Create a new Activity
        $activity = Activity::create([
            'type' => $type,
            'title' => $data['title'],
            'location' => $data['location'],
            'description' => decodeEditorData($data['description']),
            'personal_confirmation_enabled' => isset($data['personalConfirmationEnabled']),
            'personal_confirmation' => isset($data['personalConfirmationEnabled']) && isset($data['personalConfirmation'])
                ? decodeEditorData($data['personalConfirmation'])
                : null,
            'organizer' => $data['organizer'],
            'maxParticipants' => $data['maxParticipants'],
            'maxGuests' => $data['maxGuests'],
            'price' => formatPriceForDb($data['price'] ?? 0),
            'whatsappUrl' => $data['whatsappUrl'],
            'start' => $start,
            'end' => $end,
            'registrationStart' => $data['registrationStart'],
            'registrationEnd' => $data['registrationEnd'],
            'cancellationEnd' => isset($data['noCancellation']) ? null : ($data['cancellationEnd'] ?? null),
            'free_organizer_count' => $data['free_organizer_count'],
            'manual_income_entries' => !empty($manualFinanceEntries) ? $manualFinanceEntries : null,
            'manual_expense_entries' => null,
            'manual_budget' => $manualBudget,
        ]);

        // If there are questions, and they're a valid array, loop through each
        if(isset($data['questions'])){
            foreach ($data['questions'] as $questionData) {
                $question = $activity->questions()->create([
                    'type' => $questionData['type'],
                    'query' => $questionData['vraag'],
                    'price' => formatPriceForDb($questionData['prijs'] ?? 0),
                    'max_amount' => $questionData['type'] === 'number' && isset($questionData['max']) ? $questionData['max'] : null,
                ]);

                // Handle select options if the question type is 'select'
                if($questionData['type'] === 'select' && isset($questionData['options']) && is_array($questionData['options'])){
                    foreach ($questionData['options'] as $optionData){
                        $question->selectOptions()->create([
                            'option' => $optionData['optie'],
                            'price' => formatPriceForDb($optionData['prijs'] ?? 0),
                        ]);
                    }
                }
            }
        }

        // Handle image upload
        if($request->hasFile('image-upload')){
            // Use the uploadImage function to handle the image upload
            $filePath = uploadImage($request->file('image-upload'), 'images/activities/');

            $activity->update(['imagePath' => $filePath]);
        }

        return redirect()->route('activity.show', $activity)->with('success', 'Activiteit succesvol aangemaakt!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Activity $activity)
    {
        // Store the activity's applications inside of a variable, uses a sortBy callback to prioritise applications in order of the ApplicationStatus Enum
        $applicationRelations = [
            'user' => fn($q) => $q->withTrashed(),
            'guests',
            'answers.question.selectOptions',
            'payments',
        ];
        $applications = $activity->applications()->with($applicationRelations)->whereNotIn('status', [ApplicationStatus::Cancelled])->get()->sortBy(fn($app) => array_search(ApplicationStatus::from($app->status->value), ApplicationStatus::cases()));
        $reserves = $activity->applications()->with($applicationRelations)->where('status', ApplicationStatus::Reserve)->get();
        $pending = $activity->applications()->with($applicationRelations)->where('status', ApplicationStatus::Pending)->get();

        $activity->setRelation('applications', $applications);
        $applications->each(fn ($application) => $application->setRelation('activity', $activity));
        $reserves->each(fn ($application) => $application->setRelation('activity', $activity));
        $pending->each(fn ($application) => $application->setRelation('activity', $activity));

        return view('activities.read', compact('activity', 'applications', 'reserves', 'pending'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Activity $activity){
        // Show the edit form with the current activity data
        $activity->load('questions.selectOptions');

        return view('activities.edit', compact('activity'));
    }

    /**
     * Duplicate an activity and copy all configurable content.
     */
    public function copy(Activity $activity)
    {
        $activity->load('questions.selectOptions');

        $copy = $activity->replicate();
        $copy->title = $activity->title . ' (kopie)';
        $copy->cancellationEnd = $activity->cancellationEnd;
        $copy->save();

        foreach ($activity->questions as $question) {
            $questionData = [
                'type' => $question->type instanceof \BackedEnum ? $question->type->value : $question->type,
                'query' => $question->query,
                'price' => $question->price,
                'max_amount' => $question->max_amount,
            ];

            $questionCopy = $copy->questions()->create([
                'type' => $questionData['type'],
                'query' => $questionData['query'],
                'price' => $questionData['price'],
                'max_amount' => $questionData['max_amount'],
            ]);

            if ($questionData['type'] === 'number' && empty($questionData['max_amount'])) {
                $questionCopy->max_amount = null;
                $questionCopy->save();
            }

            foreach ($question->selectOptions as $option) {
                $questionCopy->selectOptions()->create([
                    'option' => $option->option,
                    'price' => $option->price,
                ]);
            }
        }

        $copy->load('questions.selectOptions');

        return redirect()->route('activity.edit', $copy)
            ->with('success', "Activiteit '{$activity->title}' is gekopieerd. Je kunt nu de kopie bewerken.");
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateActivityRequest $request, Activity $activity){
        // Collect data from request
        $data = $request->all();
        $manualFinanceEntries = $this->sanitizeManualFinanceEntries($data['manual_finance_entries'] ?? null);
        $manualBudget = $this->parseManualBudget($data['manual_budget'] ?? null);

        // Determine activity type
        $type = ActivityType::OneDay;
        if(isset($data['recurring'])){
            $type = ActivityType::Weekly;
        } elseif(isset($data['start-date'], $data['end-date']) && $data['start-date'] !== $data['end-date']){
            $type = ActivityType::MultiDay;
        }

        // Combine start and end values
        if ($type === ActivityType::Weekly) {
            $weekday = isset($data['recurring_weekday']) ? (int) $data['recurring_weekday'] : null;
            $baseDate = $weekday ? now()->copy()->setISODate((int) now()->isoWeekYear(), (int) now()->isoWeek(), $weekday) : null;
            $start = $baseDate ? $baseDate->copy()->startOfDay() : null;
            $end = $baseDate ? $baseDate->copy()->endOfDay() : null;
        } else {
            $start = isset($data['start-date']) ? $data['start-date'] . ' ' . ($data['start-time'] ?? '00:00') : null;
            $end = isset($data['end-date']) ? $data['end-date'] . ' ' . ($data['end-time'] ?? '23:59') : null;
        }

        // Update activity
        $activity->update([
            'type' => $type,
            'title' => $data['title'] ?? $activity->title,
            'location' => $data['location'] ?? $activity->location,
            'description' => isset($data['description']) ? decodeEditorData($data['description']) : $activity->description,
            'personal_confirmation_enabled' => isset($data['personalConfirmationEnabled']),
            'personal_confirmation' => isset($data['personalConfirmationEnabled']) && isset($data['personalConfirmation'])
                ? decodeEditorData($data['personalConfirmation'])
                : null,
            'organizer' => $data['organizer'] ?? $activity->organizer,
            'maxParticipants' => $data['maxParticipants'] ?? $activity->maxParticipants,
            'maxGuests' => $data['maxGuests'] ?? $activity->maxGuests,
            'price' => isset($data['price']) ? formatPriceForDb($data['price']) : $activity->price,
            'whatsappUrl' => $data['whatsappUrl'] ?? $activity->whatsappUrl,
            'start' => $start,
            'end' => $end,
            'registrationStart' => $data['registrationStart'] ?? $activity->registrationStart,
            'registrationEnd' => $data['registrationEnd'] ?? $activity->registrationEnd,
            'cancellationEnd' => isset($data['noCancellation']) ? null : ($data['cancellationEnd'] ?? $activity->cancellationEnd),
            'free_organizer_count' => $data['free_organizer_count'] ?? $activity->free_organizer_count,
            'manual_income_entries' => !empty($manualFinanceEntries) ? $manualFinanceEntries : null,
            'manual_expense_entries' => null,
            'manual_budget' => $manualBudget,
        ]);

        // Handle questions - first delete all old questions, then create new ones
        $activity->questions()->delete();
        
        // If there are questions, and they're a valid array, loop through each
        if(isset($data['questions']) && is_array($data['questions'])){
            foreach ($data['questions'] as $questionData) {
                $question = $activity->questions()->create([
                    'type' => $questionData['type'],
                    'query' => $questionData['vraag'],
                    'price' => formatPriceForDb($questionData['prijs'] ?? 0),
                    'max_amount' => $questionData['type'] === 'number' && isset($questionData['max']) ? $questionData['max'] : null,
                ]);

                // Handle select options if the question type is 'select'
                if($questionData['type'] === 'select' && isset($questionData['options']) && is_array($questionData['options'])){
                    foreach ($questionData['options'] as $optionData){
                        $question->selectOptions()->create([
                            'option' => $optionData['optie'],
                            'price' => formatPriceForDb($optionData['prijs'] ?? 0),
                        ]);
                    }
                }
            }
        }

        // Upload image if present
        if($request->hasFile('image-upload')){
            $filePath = uploadImage($request->file('image-upload'), 'images/activities/');
            $activity->update(['imagePath' => $filePath]);
        }

        return redirect()->route('activity.show', $activity)
            ->with('success', 'De activiteit is succesvol bijgewerkt!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Activity $activity){
        // Create a temporary new controller to call static methods on applications
        $controller = new ApplicationController;

        // Update all applications with admin override, update the activity type and save the instance
        $activity->applications->each(fn($application) => $controller->destroy($application, true));
//        $activity->update(['type' => ActivityType::Cancelled->value]);
        $activity->type = ActivityType::Cancelled;
        $activity->save();
        // Return on success
        return redirect()->route('activity.index')->with('success',"Activiteit '$activity->title' succesvol geannuleerd!");
    }

    public function permanentDelete(Activity $activity){
        $activity->delete();

        return redirect()->route('activity.index')->with('success',"Activiteit '$activity->title' succesvol permanent verwijderd!");
    }
}
