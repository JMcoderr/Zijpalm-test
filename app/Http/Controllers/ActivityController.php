<?php

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
use BumpCore\EditorPhp\EditorPhp;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

        // All ongoing activities that aren't cancelled, sorted by starting date/time
        $activities = Activity::where('end', '>', now())->whereNotIn('type', [ActivityType::Weekly, ActivityType::Cancelled])->get()->sortBy('start');

        // All recurring activities, since they are treated separately
        $recurringActivities = Activity::where('type', ActivityType::Weekly)->get()->sortBy('title');

        // All activities that are past the end date/time, from the last year only, with the latest ones on top, excluding weekly or cancelled activities
        $archivedActivities = Activity::where('end', '<', now())->where('end', '>=', now()->subYear())->whereNotIn('type', [ActivityType::Weekly, ActivityType::Cancelled])->get()->sortBy('end');

        // Return the index with all 3 as compact
        return view('activities.index', compact('activities', 'recurringActivities', 'archivedActivities'));
    }

    /**
     * Display the suggestion form.
     */
    public function suggestion()
    {
        //
    }

    /**
     * Process the suggestion form.
     */
    public function processSuggestion()
    {
        //
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

    /***
     * Sends a reminder email to participants registered for a given activity.
     * @param Activity $activity
     * @return \Illuminate\Http\RedirectResponse
     */
    public function notifyParticipants(NotifyParticipantsRequest $request, Activity $activity) {
        // Cast validated integers to ints, required for Power Automate's parsing.
        $validatedData = $request->validated();
        $validatedData = castValidatedInts($validatedData, ['delay', 'batch_size']);

        // Retrieve all emails of active signups.
        $emails = $this->retrieveParticipantEmails($activity, [ApplicationStatus::Active]);

        // Check if provided Power Automate variables could cause issues.
        $errorArray = $this->validationPowerAutomate($emails, $validatedData['batch_size'], $validatedData['delay']);

        if(!empty($errorArray)) {
            throw ValidationException::withMessages($errorArray)->errorBag('reminderMail');
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

        // Retrieve all emails of the members that are not signed up for the activity.
        $participantEmails = $this->retrieveParticipantEmails($activity, [ApplicationStatus::Active, ApplicationStatus::Pending, ApplicationStatus::Reserve]);
        $nonParticipantEmails = User::notSoftDeleted()->whereNotIn('email', $participantEmails)->pluck('email');

        // Check if provided Power Automate variables could cause issues.
        $errorArray = $this->validationPowerAutomate($nonParticipantEmails, $validatedData['batch_size'], $validatedData['delay']);

        if(!empty($errorArray)) {
            throw ValidationException::withMessages($errorArray)->errorBag('announcementMail');
        }

        // Power Automate json mail
        Mail::to(config('mail.bestuur.address'), config('mail.bestuur.name'))->send(new NewActivity($activity, $nonParticipantEmails));

        return redirect()->route('activity.show', $activity)->with('success', 'De aankondiging is verstuurt');
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

        // Set start and end times to avoid having a date with null.
        $start = isset($data['start-date']) && $type !== ActivityType::Weekly ? $data['start-date'] . ' ' . $data['start-time'] : null;
        $end = isset($data['end-date']) && $type !== ActivityType::Weekly ? $data['end-date'] . ' ' . $data['end-time'] : null;

        // Create a new Activity
        $activity = Activity::create([
            'type' => $type,
            'title' => $data['title'],
            'location' => $data['location'],
            'description' => decodeEditorData($data['description']),
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
        $applications = $activity->applications()->with(['user' => fn($q) => $q->withTrashed()], 'guests')->whereNotIn('status', [ApplicationStatus::Cancelled])->get()->sortBy(fn($app) => array_search(ApplicationStatus::from($app->status->value), ApplicationStatus::cases()));
        $reserves = $activity->applications()->with(['user' => fn($q) => $q->withTrashed()], 'guests')->where('status', ApplicationStatus::Reserve)->get();
        $pending = $activity->applications()->with(['user' => fn($q) => $q->withTrashed()], 'guests')->where('status', ApplicationStatus::Pending)->get();

        return view('activities.read', compact('activity', 'applications', 'reserves', 'pending'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Activity $activity){
        // Show the edit form with the current activity data
        return view('activities.edit', compact('activity'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateActivityRequest $request, Activity $activity){
        // Handle changes

        // Return success
        return redirect()->route('activity.show', $activity)->with('success', 'Activiteit succesvol aangepast!');
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
        if($activity->type === ActivityType::Archived || $activity->type === ActivityType::Weekly || ($activity->start < now() && $activity->end < now())) {
            $activity->delete();
            return redirect()->route('activity.index')->with('success',"Activiteit '$activity->title' succesvol verwijderd!");
        } else {
            return redirect()->back()->with('error',"Activiteit '$activity->title' is nog niet verlopen en kan dus niet verwijderd worden!");
        }
    }
}
