<?php
// This file is part of the app logic and has a short comment so it is easier to read.


namespace App\Http\Controllers;

use App\ActivityType;
use App\FileType;
use App\Imports\MembersImport;
use App\ApplicationStatus;
use App\Http\Requests\NotifyAllMembersRequest;
use App\Http\Requests\NotifyNewEmployeesRequest;
use App\Imports\NotifyImport;
use App\Imports\UsersImport;
use App\Jobs\SendAnnualInvoice;
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
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

class AdminController extends Controller
{
    public function activities()
    {
        // Split one-day and multi-day first, then merge into one upcoming list.
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

        // Send grouped collections to the admin activities page.
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

    public function sendAnnualInvoices(Request $request)
    {
        $data = $request->validateWithBag('annualInvoice', [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        $amount = (float) $data['amount'];
        $year = (int) $data['year'];

        $users = User::notSoftDeleted()
            ->where('type', UserType::Gepensioneerde)
            ->orderBy('firstName')
            ->get();

        if ($users->isEmpty()) {
            return redirect()->route('admin.users')->with('error', 'Er zijn geen actieve gepensioneerden om te factureren.');
        }

        foreach ($users as $user) {
            SendAnnualInvoice::dispatch($user->id, $amount, $year);
        }

        return redirect()->route('admin.users')->with('success', "{$users->count()} jaarfacturen voor gepensioneerden in wachtrij geplaatst. Verwerking gebeurt op de achtergrond.");
    }

    public function exportUsers()
    {
        // Export only active users and keep a stable sort order for readability.
        $users = User::notSoftDeleted()
            ->orderBy('type')
            ->orderBy('firstName')
            ->get();

        $fileName = 'ledenlijst_' . now()->format('Ymd_His') . '.xlsx';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Leden');

        $headers = [
            'First name',
            'Last name',
            'Email',
            'Phone',
            'Type',
            'Employee number',
            'Contribution',
            'Is admin',
        ];

        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($users as $user) {
            $phone = formatPhoneNumber($user->phone);

            $sheet->fromArray([
                $user->firstName,
                $user->lastName,
                $user->email,
                $phone,
                $user->type->value,
                $user->employee_number,
                number_format((float) $user->contribution, 2, '.', ''),
                $user->is_admin ? 'yes' : 'no',
            ], null, 'A' . $row);

            // Force text to preserve the leading 0 in Excel.
            $sheet->setCellValueExplicit('D' . $row, $phone, DataType::TYPE_STRING);

            $row++;
        }

        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $writer = new XlsxWriter($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'members_');
        $writer->save($tempFile);

        // Download and remove the temporary file after the response is sent.
        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    public function exportDeletedUsers()
    {
        // Export deleted users in the same readable format, including when they were moved to old members.
        $users = User::softDeletedPast()
            ->orderBy('deleted_at', 'desc')
            ->orderBy('firstName')
            ->get();

        $fileName = 'oud_leden_' . now()->format('Ymd_His') . '.xlsx';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Oud leden');

        $headers = [
            'First name',
            'Last name',
            'Email',
            'Phone',
            'Former type',
            'Deleted at',
            'Is admin',
        ];

        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($users as $user) {
            $phone = formatPhoneNumber($user->phone);
            $formerType = $user->type?->value ?? (string) $user->type;
            $deletedAt = optional($user->deleted_at)->format('d-m-Y H:i');

            $sheet->fromArray([
                $user->firstName,
                $user->lastName,
                $user->email,
                $phone,
                $formerType,
                $deletedAt,
                $user->is_admin ? 'yes' : 'no',
            ], null, 'A' . $row);

            // Force text to preserve the leading 0 in Excel.
            $sheet->setCellValueExplicit('D' . $row, $phone, DataType::TYPE_STRING);

            $row++;
        }

        foreach (range('A', 'G') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $writer = new XlsxWriter($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'deleted_members_');
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
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
        $years = $reports->whereNotNull('year')->sortBy('year')->values();

        return view('admin.reports', compact('activities', 'years'));
    }

    public function content()
    {
        // Group regular content blocks so they are easy to manage in the admin UI.
        $textContent = Content::getByType('text')->sortBy('name');
        $boardMemberContent = Content::getByType('bestuurslid')->sortBy('name');
        $files = Content::getByType('file')->sortBy('name');
        
        // Define the custom email order
        $emailOrder = [
            'email-activiteit-aangemeld',
            'email-activiteit-afgemeld',
            'email-betaling-mislukt',
            'email-nieuwe-activiteit',
            'email-toekomstige-activiteiten',
            'email-herinnering-activiteit-deelnemers',
            'email-activiteit-aangemeld-reserve',
            'email-reserve-upgrade',
            'email-reset-wachtwoord',
            'email-bestelling-betaald',
            'email-nieuw-lid',
            'email-bestuur-activiteit-aanmeldingen',
            'email-bestuur-nieuwe-bestelling',
            'email-bestuur-nieuwe-leden',
            'email-herinnering-activiteit-niet-deelnemers',
        ];
        
        // Create a lookup map so we can sort emails by the custom order.
        $positionMap = array_flip($emailOrder);
        
        $allEmails = Content::getByType('email');
        $emails = $allEmails->sort(function ($a, $b) use ($positionMap) {
            $posA = $positionMap[$a->name] ?? PHP_INT_MAX;
            $posB = $positionMap[$b->name] ?? PHP_INT_MAX;
            return $posA <=> $posB;
        })->values();

        $contentGroups = [
            'Tekst' => $textContent,
            'Bestuursleden' => $boardMemberContent,
            'Bestanden' => $files,
            'E-mail' => $emails,
        ];

        // Render the grouped content overview page.
        return view('admin.content', compact('contentGroups'));
    }

    /**
     * The GET request displaying the notify all members page.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function notifyAllMembers(){
        $recipientCount = User::notSoftDeleted()->count();

        return view('admin.notify-all-members', compact('recipientCount'));
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
     * Return the number of unique recipient emails in the uploaded employee list.
     */
    public function previewNewEmployeesCount(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'employee_list' => 'required|file|mimes:xls,xlsx,csv|max:10240',
        ]);

        if ($validated->fails()) {
            return response()->json([
                'message' => $validated->errors()->first('employee_list'),
            ], 422);
        }

        $dataRows = Excel::toCollection(new NotifyImport, $request->file('employee_list'))->first() ?? collect();

        $emails = $dataRows
            ->flatMap(function ($row) {
                return collect($row)
                    ->map(fn($cell) => trim((string) $cell))
                    ->filter(fn($cell) => filter_var($cell, FILTER_VALIDATE_EMAIL));
            })
            ->map(fn($email) => mb_strtolower($email))
            ->unique()
            ->values();

        return response()->json([
            'recipient_count' => $emails->count(),
        ]);
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

        $dataRows = Excel::toCollection(new NotifyImport, $request->file('employee_list'))->first() ?? collect();

        // Extract email addresses from any column in the uploaded sheet.
        $emails = $dataRows
            ->flatMap(function ($row) {
                return collect($row)
                    ->map(fn($cell) => trim((string) $cell))
                    ->filter(fn($cell) => filter_var($cell, FILTER_VALIDATE_EMAIL));
            })
            ->map(fn($email) => mb_strtolower($email))
            ->unique()
            ->values();

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
        // Validate import input before touching the Excel importer.
        $validator = Validator::make($request->all(), [
            'import-employees-form-members-list' => 'required|file|mimes:xls,xlsx,csv|max:10240',
        ], [
            'import-employees-form-members-list.required' => 'Please upload a file.',
            'import-employees-form-members-list.mimes'    => 'The members file must be an Excel or CSV file.',
            'import-employees-form-members-list.max'      => 'The file may not be larger than 10MB.',
        ]);

        if ($validator->fails())
            return back()->withErrors($validator, 'importEmployees');

        // Large imports can take a while, so disable execution limits for this request.
        set_time_limit(0);
        ini_set('max_execution_time', 0);
//        dd($request->all());

        // Disable query logging to reduce memory usage during large imports.
        DB::connection()->disableQueryLog();
        Excel::import(new UsersImport, $request->file('import-employees-form-members-list'));
        DB::connection()->enableQueryLog();
        return back()->with('success', 'Gebruikers succesvol geïmporteerd.');
    }

    public function importMembers(Request $request)
    {
        // Validate the uploaded members file first.
        $validator = Validator::make($request->all(), [
            'import-members-form-members-list' => 'required|file|mimes:xls,xlsx,csv|max:10240',
        ], [
            'import-members-form-members-list.required' => 'Please upload a file.',
            'import-members-form-members-list.mimes'    => 'The members file must be an Excel or CSV file.',
            'import-members-form-members-list.max'      => 'The file may not be larger than 10MB.',
        ]);

        if ($validator->fails())
            return back()->withErrors($validator, 'importMembers');

        // Imports can be heavy, so keep time limit open here as well.
        set_time_limit(0);
        ini_set('max_execution_time', 0);
//        dd($request->all());

        // Disable query log to avoid memory spikes on big files.
        DB::connection()->disableQueryLog();
        Excel::import(new MembersImport, $request->file('import-members-form-members-list'));
        DB::connection()->enableQueryLog();
        return back()->with('success', 'Gebruikers succesvol geïmporteerd.');
    }

    public function removeUser(Request $request, User $user)
    {
        // Soft-delete a user from the admin panel.
        $user->delete();
        return back()->with('success', 'Gebruikers lidmaatschap succesvol afgemeld.');
    }

    public function reinstateUser(Request $request, User $user)
    {
        // Restore a previously soft-deleted user.
        $user->restore();
        return back()->with('success', 'Gebruikers lidmaatschap succesvol hersteld.');
    }
}
