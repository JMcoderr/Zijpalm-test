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
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Report;
use App\Models\User;
use App\PaymentStatus;
use App\UserType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Builder;
use Mollie\Laravel\Facades\Mollie;
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

    public function users(Request $request)
    {
        set_time_limit(0);
        ini_set('max_execution_time', 0);

        // Admins can search the member list by name (or e-mail), every word has to match something.
        $search = trim((string) $request->query('zoek', ''));
        $searchTerms = $search === '' ? [] : preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);

        $applySearch = function ($query) use ($searchTerms) {
            if (empty($searchTerms)) {
                return $query;
            }

            return $query->where(function ($outer) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $outer->where(function ($inner) use ($term) {
                        $inner->where('firstName', 'like', '%' . $term . '%')
                              ->orWhere('lastName', 'like', '%' . $term . '%')
                              ->orWhere('email', 'like', '%' . $term . '%');
                    });
                }
            });
        };

        $medewerkers = $applySearch(User::getByType(UserType::Medewerker)->notSoftDeleted())->orderBy('firstName')->get();
        $stagiairs = $applySearch(User::getByType(UserType::Stagiair)->notSoftDeleted())->orderBy('firstName')->get();
        $inhuur = $applySearch(User::getByType(UserType::Inhuur)->notSoftDeleted())->orderBy('firstName')->get();
        $gepensioneerden = $applySearch(User::getByType(UserType::Gepensioneerde)->notSoftDeleted())->orderBy('firstName')->get();
        $ereleden = $applySearch(User::getByType(UserType::EreLid)->notSoftDeleted())->orderBy('firstName')->get();

        // Get all the deleted users sorted by newest delete date first
        $deletedUsers = $applySearch(User::softDeletedPast())->orderBy('deleted_at', 'desc')->get();

        $admins = User::where("is_admin", true)->where('type', '!=', UserType::System)->get()->count();

        $userGroups = [
            'Medewerkers' => $medewerkers,
            'Stagiairs' => $stagiairs,
            'Inhuur' => $inhuur,
            'Gepensioneerden' => $gepensioneerden,
            'Ereleden' => $ereleden,
        ];

        // The overview table always shows the real totals, so a search does not change the numbers.
        $userCounts = [
            'Medewerkers' => User::getByType(UserType::Medewerker)->notSoftDeleted()->count(),
            'Stagiairs' => User::getByType(UserType::Stagiair)->notSoftDeleted()->count(),
            'Inhuur' => User::getByType(UserType::Inhuur)->notSoftDeleted()->count(),
            'Gepensioneerden' => User::getByType(UserType::Gepensioneerde)->notSoftDeleted()->count(),
            'Ereleden' => User::getByType(UserType::EreLid)->notSoftDeleted()->count(),
        ];

        return view('admin.users', compact('userGroups', 'userCounts', 'admins', 'deletedUsers', 'search'));
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
            'Membership start date',
            'Employee number',
            'Contribution',
            'Is admin',
        ];

        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($users as $user) {
            $phone = filled($user->phone) ? formatPhoneNumber($user->phone) : '';

            $sheet->fromArray([
                $user->firstName,
                $user->lastName,
                $user->email,
                $phone,
                $user->type->value,
                optional($user->membership_start_date)->format('d-m-Y'),
                $user->employee_number,
                number_format((float) $user->contribution, 2, '.', ''),
                $user->is_admin ? 'yes' : 'no',
            ], null, 'A' . $row);

            // Force text to preserve the leading 0 in Excel.
            $sheet->setCellValueExplicit('D' . $row, $phone, DataType::TYPE_STRING);

            $row++;
        }

        foreach (range('A', 'I') as $column) {
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
            'Membership start date',
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
                optional($user->membership_start_date)->format('d-m-Y'),
                $deletedAt,
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

    private function filteredOrders(Request $request): Builder
    {
        $filters = $request->validate([
            'type' => ['nullable', Rule::in(['product', 'activity'])],
            'product_id' => ['nullable', 'integer', Rule::exists('products', 'id')],
        ]);

        return Order::query()->webshop()
            ->with(['user' => fn ($query) => $query->withTrashed(), 'payment', 'products'])
            ->when(filled($filters['type'] ?? null) || filled($filters['product_id'] ?? null), function (Builder $query) use ($filters) {
                $query->whereHas('products', function (Builder $query) use ($filters) {
                    if (filled($filters['type'] ?? null)) {
                        $query->ofType($filters['type']);
                    }
                    if (filled($filters['product_id'] ?? null)) {
                        $query->whereKey($filters['product_id']);
                    }
                });
            });
    }

    public function orders(Request $request)
    {
        $orders = $this->filteredOrders($request)
            ->orderByDesc('id')
            ->get();
        $products = Product::query()->whereHas('orders', fn (Builder $query) => $query->webshop())
            ->orderBy('name')->get();

        $isGuestOrder = function (Order $order): bool {
            if ($order->is_guest_checkout !== null) {
                return (bool) $order->is_guest_checkout;
            }

            // Fallback for very old orders before the dedicated guest flag existed.
            return $order->user?->password === null;
        };

        $guestOrders = $orders->filter($isGuestOrder)->values();
        $memberOrders = $orders->reject($isGuestOrder)->values();

        $statusCounts = function ($collection): array {
            return [
                'paid' => $collection->filter(fn ($order) => $order->payment?->status === PaymentStatus::paid)->count(),
                'pending' => $collection->filter(fn ($order) => in_array($order->payment?->status, [PaymentStatus::pending, PaymentStatus::open, PaymentStatus::authorized], true))->count(),
                'failed' => $collection->filter(fn ($order) => in_array($order->payment?->status, [PaymentStatus::failed, PaymentStatus::expired, PaymentStatus::canceled], true))->count(),
            ];
        };

        $memberStatusCounts = $statusCounts($memberOrders);
        $guestStatusCounts = $statusCounts($guestOrders);

        return view('admin.orders', compact('memberOrders', 'guestOrders', 'memberStatusCounts', 'guestStatusCounts', 'products'));
    }

    public function downloadOrders(Request $request)
    {
        $orders = $this->filteredOrders($request)->orderByDesc('id')->get();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Bestellingen');
        $headers = ['Bestelling', 'Besteld op', 'Klanttype', 'Naam', 'E-mail', 'Telefoon',
            'Betaalstatus', 'Item', 'Type', 'Aantal', 'Prijs per stuk', 'Subtotaal', 'Bestelling totaal'];
        $sheet->fromArray($headers);
        $sheet->getStyle('A1:M1')->getFont()->setBold(true);
        $row = 2;

        foreach ($orders as $order) {
            foreach ($order->products as $product) {
                if ($request->filled('product_id') && $product->id !== (int) $request->input('product_id')) {
                    continue;
                }
                if ($request->filled('type') && ($product->isActivityProduct() ? 'activity' : 'product') !== $request->input('type')) {
                    continue;
                }

                $unitPrice = (float) ($product->pivot->unit_price ?? $product->price);
                $values = [
                    $order->id,
                    $order->payment->created_at?->format('d-m-Y H:i'),
                    ($order->is_guest_checkout ?? ($order->user?->password === null)) ? 'Gast' : 'Lid',
                    $order->user?->name,
                    $order->user?->email,
                    $order->user?->phone,
                    $order->payment->status->value,
                    $product->name,
                    $product->isActivityProduct() ? 'Activiteit' : 'Product',
                    (int) $product->pivot->quantity,
                    $unitPrice,
                    $unitPrice * $product->pivot->quantity,
                    (float) $order->payment->price,
                ];

                foreach ($values as $index => $value) {
                    $sheet->setCellValueExplicit(
                        \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1) . $row,
                        $value ?? '',
                        is_int($value) || is_float($value) ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING
                    );
                }
                $row++;
            }
        }

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:M' . max(1, $row - 1));
        if ($row > 2) {
            $sheet->getStyle('K2:M' . ($row - 1))->getNumberFormat()->setFormatCode('0.00');
        }
        foreach (range('A', 'M') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            try {
                (new XlsxWriter($spreadsheet))->save('php://output');
            } finally {
                $spreadsheet->disconnectWorksheets();
            }
        }, 'bestellingen_' . now()->format('Ymd_His') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function showOrder(Order $order)
    {
        $order->load(['user', 'payment', 'products']);

        // Guard against opening non-webshop orders directly by URL.
        if (!str_starts_with((string) $order->payment?->description, 'Bestelling #')) {
            abort(404);
        }

        return view('admin.order-show', compact('order'));
    }

    public function deletePendingOrder(Order $order)
    {
        $order->loadMissing('payment');

        if (!str_starts_with((string) $order->payment?->description, 'Bestelling #')) {
            abort(404);
        }

        if (!$order->isAwaitingPayment()) {
            return back()->with('error', 'Alleen bestellingen met een betaling in afwachting kunnen worden verwijderd.');
        }

        try {
            $molliePayment = Mollie::api()->payments->get($order->payment->mollieId);

            if ($molliePayment->status === PaymentStatus::paid->value) {
                $order->payment->status = PaymentStatus::paid;
                $order->payment->paidAt = $molliePayment->paidAt ?? now();
                $order->payment->save();

                return back()->with('error', 'Mollie meldt dat deze bestelling betaald is. De bestelling is daarom niet verwijderd.');
            }

            if (in_array($molliePayment->status, [
                PaymentStatus::open->value,
                PaymentStatus::pending->value,
                PaymentStatus::authorized->value,
            ], true)) {
                Mollie::api()->payments->cancel($order->payment->mollieId);
            } elseif (!in_array($molliePayment->status, [
                PaymentStatus::canceled->value,
                PaymentStatus::expired->value,
                PaymentStatus::failed->value,
            ], true)) {
                return back()->with('error', 'Mollie gaf een onbekende betaalstatus terug. De bestelling is niet verwijderd.');
            }
        } catch (\Throwable $exception) {
            Log::warning('[AdminController] Pending order payment could not be canceled at Mollie', [
                'order_id' => $order->id,
                'payment_id' => $order->payment_id,
                'error' => $exception->getMessage(),
            ]);

            return back()->with('error', 'De betaling kon niet bij Mollie worden gecontroleerd of geannuleerd. De bestelling is niet verwijderd. Mollie meldt: ' . $exception->getMessage());
        }

        $result = DB::transaction(function () use ($order) {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->first();

            if (!$lockedOrder) {
                $payment = Payment::query()->whereKey($order->payment_id)->lockForUpdate()->first();

                if ($payment?->status === PaymentStatus::paid) {
                    return 'paid';
                }

                if ($payment) {
                    $payment->status = PaymentStatus::canceled;
                    $payment->save();
                }

                return 'deleted';
            }

            $payment = Payment::query()->whereKey($lockedOrder->payment_id)->lockForUpdate()->firstOrFail();

            if ($payment->status === PaymentStatus::paid) {
                return 'paid';
            }

            $lockedOrder->load('products');

            foreach ($lockedOrder->products as $product) {
                if ($product->stock === null) {
                    continue;
                }

                $lockedProduct = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
                $lockedProduct->increment('stock', (int) $product->pivot->quantity);
            }

            $payment->status = PaymentStatus::canceled;
            $payment->save();
            $lockedOrder->delete();

            return 'deleted';
        });

        if ($result === 'paid') {
            return back()->with('error', 'De bestelling is inmiddels betaald en kan niet worden verwijderd.');
        }

        return redirect()->route('admin.orders')->with('success', 'De openstaande bestelling is geannuleerd en verwijderd.');
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

    public function permanentlyDeleteUser(Request $request, User $user)
    {
        // Permanently remove a user from the database.
        if ($user->type === UserType::System) {
            return back()->with('error', 'Systeemgebruikers kunnen niet definitief verwijderd worden.');
        }

        $userName = $user->name;
        $user->forceDelete();

        return back()->with('success', "Gebruiker {$userName} is definitief verwijderd.");
    }
}
