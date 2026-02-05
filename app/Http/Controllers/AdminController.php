<?php

namespace App\Http\Controllers;

use App\ActivityType;
use App\FileType;
use App\Imports\MembersImport;
use App\ApplicationStatus;
use App\Http\Requests\NotifyAllMembersRequest;
use App\Http\Requests\NotifyNewEmployeesRequest;
use App\Imports\NotifyImport;
use App\Imports\UsersImport;
use App\Mail\ActivityReminder;
use App\Mail\NotifyAllMembers;
use App\Mail\NotifyNewEmployees;
use App\Models\Activity;
use App\Models\Content;
use App\Models\Report;
use App\Models\User;
use App\UserType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class AdminController extends Controller
{
    public function activities()
    {
        $oneDayActivities = Activity::getByType(ActivityType::OneDay);
        $multiDayActivities = Activity::getByType(ActivityType::MultiDay);

        // Combine one-day and multi-day activities into a single collection
        $upcomingActivities = $oneDayActivities->merge($multiDayActivities);
        $upcomingActivities = $upcomingActivities->sortBy('start');

        $weeklyActivities = Activity::getByType(ActivityType::Weekly);
        $archivedActivities = Activity::getByType(ActivityType::Archived);

        $activityGroupsWithDate = [
            'Aankomende activiteiten' => $upcomingActivities,
            'Verlopen activiteiten' => $archivedActivities,
        ];

        $activityGroupsWithoutDate = [
            'Herhalende activiteiten' => $weeklyActivities,
        ];

        return view('admin.activities', compact('activityGroupsWithDate', 'activityGroupsWithoutDate'));
    }

    public function downloadApplications(Activity $activity)
    {
        // Check if there are an active sign-ups that have completed their payment
        $applications = $activity->applications()->where('status', ApplicationStatus::Active)->count();

        if($applications <= 0){
            return redirect()->back()->with('error', 'Er zijn geen deelnemers die de betaling succesvol hebben afgerond.');
        }

        // Generate the Excel file for the activity applications
        $excelFile = $activity->createApplicationsExcelFile();

        // Return the file as a download response
        return response()->download($excelFile['filePath'], $excelFile['fileName'])->deleteFileAfterSend(true);
    }

    public function settings()
    {
        return view('admin.settings');
    }

    public function users()
    {
        set_time_limit(0);
        ini_set('max_execution_time', 0);

        $medewerkers = User::getByType(UserType::Medewerker)->notSoftDeleted()->orderBy('firstName')->get();
        $stagiairs = User::getByType(UserType::Stagiair)->notSoftDeleted()->orderBy('firstName')->get();
        $inhuur = User::getByType(UserType::Inhuur)->notSoftDeleted()->orderBy('firstName')->get();
        $gepensioneerden = User::getByType(UserType::Gepensioneerde)->notSoftDeleted()->orderBy('firstName')->get();
        $ereleden = User::getByType(UserType::EreLid)->notSoftDeleted()->orderBy('firstName')->get();

        // Get all the deleted users sorted by newest delete date first
        $deletedUsers = User::softDeletedPast()->orderBy('deleted_at', 'desc')->get();

        $admins = User::where("is_admin", true)->where('type', '!=', UserType::System)->get()->count();

        $userGroups = [
            'Medewerkers' => $medewerkers,
            'Stagiairs' => $stagiairs,
            'Inhuur' => $inhuur,
            'Gepensioneerden' => $gepensioneerden,
            'Ereleden' => $ereleden,
        ];

        return view('admin.users', compact('userGroups', 'admins', 'deletedUsers'));
    }

    public function reports()
    {
        // Get all non-cancelled & non-weekly activities
        // Seperate into activities with and without reports
//        [$activitiesWithReport, $activitiesWithoutReport] = Activity::whereNotIn('type', [ActivityType::Cancelled, ActivityType::Weekly])
//            ->get()
//            ->sortBy('start')
//            ->partition(fn($activity) => $activity->report);
//
//        $activityGroups = [
//            'Activiteiten zonder verslag' => $activitiesWithoutReport,
//            'Activiteiten met verslag' => $activitiesWithReport,
//        ];
//
//        $yearlyReports = Content::getByType('year-report')->sortByDesc('created_at');
//
//        return view('admin.reports', compact('activityGroups', 'yearlyReports'));
        $reports = Report::query()->orderByDesc('created_at')->withWhereHas('content', fn($q) => $q->where('fileType', FileType::Pdf))->get();
        $activities = $reports->whereNull('year');
        $years = $reports->whereNotNull('year');

        return view('admin.reports', compact('activities', 'years'));
    }

    public function content()
    {
        $textContent = Content::getByType('text')->sortBy('name');
        $boardMemberContent = Content::getByType('bestuurslid')->sortBy('name');
        $files = Content::getByType('file')->sortBy('name');
        $emails = Content::getByType('email')->sortBy('name');

        $contentGroups = [
            'Tekst' => $textContent,
            'Bestuursleden' => $boardMemberContent,
            'Bestanden' => $files,
            'E-mail' => $emails,
        ];

        return view('admin.content', compact('contentGroups'));
    }

    /**
     * The GET request displaying the notify all members page.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function notifyAllMembers(){
        return view('admin.notify-all-members');
    }

    /**
     *  The POST request logic for sending all members an email.
     *
     * @param NotifyAllMembersRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function notifyAllMembersPOST(NotifyAllMembersRequest $request){
        // Cast validated integers to ints, required for Power Automate's parsing.
        $validatedData = $request->validated();
        $validatedData = castValidatedInts($validatedData, ['delay', 'batch_size']);

        // Retrieve the emails of all active members.
        $emails = User::notSoftDeleted()->pluck('email');

        // Check if provided Power Automate variables could cause issues.
        $errorArray = $this->validationPowerAutomate($emails, $validatedData['batch_size'], $validatedData['delay']);

        if(!empty($errorArray)) {
            return back()->withErrors($errorArray);
        }

        // Power Automate Json mail.
        Mail::to(config('mail.bestuur.address'), config('mail.bestuur.name'))->send(new NotifyAllMembers($emails, $validatedData));

        return redirect()->route('admin.notifyAllMembers')->with('success', 'Het bericht is verstuurd!');
    }

    /**
     * The GET request displaying the notify new employees page.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function notifyNewEmployees(){
        return view('admin.notify-new-employees');
    }

    /**
     *  The POST request logic for sending new employees an email.
     *
     * @param NotifyNewEmployeesRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function notifyNewEmployeesPOST(NotifyNewEmployeesRequest $request){
        // Cast validated integers to ints, required for Power Automate's parsing.
        $validatedData = $request->validated();
        $validatedData = castValidatedInts($validatedData, ['delay', 'batch_size']);

        $dataRows = Excel::toCollection(new NotifyImport, $request->file('employee_list'))->first();

        // Ignore warning on pluck, it does not recognize $dataRows type, but it does work.
        $emails = $dataRows->pluck(8)->filter(fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL))->values();

        // Check if provided Power Automate variables could cause issues.
        $errorArray = $this->validationPowerAutomate($emails, $validatedData['batch_size'], $validatedData['delay']);

        if(!empty($errorArray)) {
            return back()->withErrors($errorArray);
        }

        // Power Automate Json mail
        Mail::to(config('mail.bestuur.address'), config('mail.bestuur.name'))->send(new NotifyNewEmployees($emails, $validatedData));

        return redirect()->route('admin.notifyNewEmployees')->with('success', 'Het bericht is verstuurd!');
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

    public function importEmployees(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'import-employees-form-members-list' => 'required|file|mimes:xls,xlsx,csv|max:10240',
        ], [
            'import-employees-form-members-list.required' => 'Please upload a file.',
            'import-employees-form-members-list.mimes'    => 'The members file must be an Excel or CSV file.',
            'import-employees-form-members-list.max'      => 'The file may not be larger than 10MB.',
        ]);

        if ($validator->fails())
            return back()->withErrors($validator, 'importEmployees');

        set_time_limit(0);
        ini_set('max_execution_time', 0);
//        dd($request->all());
        DB::connection()->disableQueryLog();
        Excel::import(new UsersImport, $request->file('import-employees-form-members-list'));
        DB::connection()->enableQueryLog();
        return back()->with('success', 'Gebruikers succesvol geïmporteerd.');
    }

    public function importMembers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'import-members-form-members-list' => 'required|file|mimes:xls,xlsx,csv|max:10240',
        ], [
            'import-members-form-members-list.required' => 'Please upload a file.',
            'import-members-form-members-list.mimes'    => 'The members file must be an Excel or CSV file.',
            'import-members-form-members-list.max'      => 'The file may not be larger than 10MB.',
        ]);

        if ($validator->fails())
            return back()->withErrors($validator, 'importMembers');

        set_time_limit(0);
        ini_set('max_execution_time', 0);
//        dd($request->all());
        DB::connection()->disableQueryLog();
        Excel::import(new MembersImport, $request->file('import-members-form-members-list'));
        DB::connection()->enableQueryLog();
        return back()->with('success', 'Gebruikers succesvol geïmporteerd.');
    }

    public function removeUser(Request $request, User $user)
    {
        $user->delete();
        return back()->with('success', 'Gebruikers lidmaatschap succesvol afgemeld.');
    }

    public function reinstateUser(Request $request, User $user)
    {
        $user->restore();
        return back()->with('success', 'Gebruikers lidmaatschap succesvol hersteld.');
    }
}
