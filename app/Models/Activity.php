<?php

namespace App\Models;

use App\ActivityType;
use App\Mail\ActivityApplied;
use App\Mail\ReserveUpgrade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use BumpCore\EditorPhp\EditorPhp;
use App\ApplicationStatus;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

class Activity extends Model
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'location',
        'description',
        'organizer',
        'maxParticipants',
        'maxGuests',
        'price',
        'whatsappUrl',
        'start',
        'end',
        'registrationStart',
        'registrationEnd',
        'cancellationEnd',
        'imagePath',
        'type'
    ];

    protected $casts = [
        'type' => ActivityType::class,
        'start' => 'datetime',
        'end' => 'datetime',
        'registrationStart' => 'datetime',
        'registrationEnd' => 'datetime',
        'cancellationEnd' => 'datetime',
    ];

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function report()
    {
        return $this->hasOne(Report::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    /**
     * Return whether or not a certain period is ongoing
     */
    public function getPeriodAttribute()
    {
        // Return true for all if it's a weekly activity
        if($this->type === ActivityType::Weekly){
            return (object)['registration' => true, 'cancellation' => true, 'activity' => true];
        }

        // Return object, $activity->period->periodName will result in a true or false
        return (object)[
            // startOfDay() and endOfDay() are redundant, as activity creation should set these properly, but I am insecure
            'registration' => now()->between($this->registrationStart->startOfDay(), $this->registrationEnd->endOfDay()),
            'cancellation' => $this->cancellationEnd
                ? now()->between($this->registrationStart->startOfDay(), $this->cancellationEnd->endOfDay())
                : false,
            'activity' => now()->between($this->start, $this->end),
        ];
    }

    public function isCancelled(): bool
    {
        return $this->type === ActivityType::Cancelled;
    }

    /**
     * Get the activity's image
     */
    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn () => Storage::url($this->imagePath)
        );
    }

    /**
     * Convert the description to HTML
     */
    protected function descriptionHTML(): Attribute
    {
        return Attribute::make(
            get: fn () => EditorPhp::make($this->description)->toHtml()
        );
    }

    public function getParticipantsAttribute(){
        $applications = $this->applications->whereNotIn('status', [ApplicationStatus::Cancelled]);

        return (object)[
            'all' => $applications,
            'capacity' => $this->maxParticipants ? max(0, $this->maxParticipants - $applications->whereNotIn('status', [ApplicationStatus::Reserve])->sum('participants')) : null,
            'active' => $applications->where('status', ApplicationStatus::Active),
            'pending' => $applications->where('status', ApplicationStatus::Pending),
            'reserve' => $applications->where('status', ApplicationStatus::Reserve),
        ];
    }

    // Returns a user's application for the activity, if it's not cancelled
    public function userApplied()
    {
        return $this->applications()->where('user_id', auth()->id())->whereNotIn('status', [ApplicationStatus::Cancelled])->first();
    }

    // Returns a report if the activity has one
    public function hasReport()
    {
        return $this->report()->first();
    }

    // Update the applications, changing reserve applications to pending if there are spots available
    public function updateApplications()
    {
        // Track the changed Applications
        $updatedApplications = [];

        // Sum of the newly added participants
        $addedParticipants = 0;

        // Sum of currently non-cancelled, non-reserve applications
        $currentParticipants = $this->applications()->whereNotIn('status', [ApplicationStatus::Cancelled, ApplicationStatus::Reserve])->sum('participants');

        // Get all reserve applications, ordered by created_at, check if they can be upgraded to pending
        foreach($this->applications()->where('status', ApplicationStatus::Reserve)->orderBy('created_at')->get() as $reserve){
            if(($currentParticipants + $reserve->participants + $addedParticipants) <= $this->maxParticipants){
                // Check if the cost of the activity is 0, if that's the case no need to set the it pending.
                if($this->price <= 0.0){
                    $reserve->update(['status' => ApplicationStatus::Active]);
                } else {
                    $reserve->update(['status' => ApplicationStatus::Pending]);
                }
                $updatedApplications[] = $reserve;
                // Add the new participants to the current count
                $addedParticipants += $reserve->participants;
            }
        }

        // For all the updated applications, do something
        foreach($updatedApplications as $application){
            if($application->status === ApplicationStatus::Active){
                // Mail the user to notify them for their successful signup for the event, no payment required for a free event.
                Mail::to(config('mail.bestuur.address'), config('mail.bestuur.name'))->send(new ActivityApplied($this, $application->user));
            } else {
                // Mail the user to notify them for getting off the reserve list
                Mail::to(config('mail.bestuur.address'), config('mail.bestuur.name'))->send(new ReserveUpgrade($application));
            }
        }
    }

    /**
     * Format the dates and times for readability
     */
    public function getFormattedDatesAndTimesAttribute(){

        // Define the returned object including defaults
        $object = (object)[
            'activity' => (object)[
                'start' => (object)['date' => formatDate($this->start), 'time' => formatTime($this->start)],
                'end' => (object)['date' => formatDate($this->end), 'time' => formatTime($this->end)],
                'full' => null,
            ],
            'registration' => (object)[
                'start' => (object)['date' => formatDate($this->registrationStart), 'time' => formatTime($this->registrationStart)],
                'end' => (object)['date' => formatDate($this->registrationEnd), 'time' => formatTime($this->registrationEnd)],
                'full' => formatDate($this->registrationStart) . ' t/m ' . formatDate($this->registrationEnd),
            ],
            'cancellation' => (object)[
                'start' => (object)['date' => formatDate($this->registrationStart), 'time' => formatTime($this->registrationStart)],
                'end' => (object)['date' => formatDate($this->cancellationEnd), 'time' => formatTime($this->cancellationEnd)],
                'full' => formatDate($this->registrationStart) . ' t/m ' . formatDate($this->cancellationEnd),
            ],
        ];

        // Switch based on ActivityType Enum
        switch($this->type){

            // For one-day activities, formatted as '1 januarie, 15:00-17:00'
            case ActivityType::OneDay:
                $object->activity->full = formatDate($this->start).", ".formatTime($this->start).($this->end ? " - ".formatTime($this->end) : "");
                break;

            // For multi-day activities, formatted as '12 november 2026 t/m 20 januari 2027'
            case ActivityType::MultiDay:
                $object->activity->full = formatDate($this->start)." t/m ".($this->end ? formatDate($this->end) : "");
                break;

            // For weekly activities, formatted as 'Wekelijks' if no start or end dates/times have been given, otherwise formatted as 'Elke Vrijdag, 17:00-18:00'
            case ActivityType::Weekly:
                if(!$this->start || !$this->end){
                    $object->activity->full = 'Wekelijks';
                }
                else{
                    $object->activity->full = "Elke ".$this->start->isoFormat('l').", ".formatTime($this->start).($this->end ? " - ".formatTime($this->end) : "");
                }
                break;

            // For cancelled activities, simply set to a string that says it's cancelled
            case ActivityType::Cancelled:
                $object->activity->full = 'Geannuleerd';
                break;

            // For archived activities, simply set to a string that says it's archived
            case ActivityType::Archived:
                $object->activity->full = 'Gearchiveerd';
                break;
        }

        return $object;
    }

    /**
     * Retrieve all activities of a specific type, sorted by start date.
     *
     * @param  \App\ActivityType  $type
     * @return \Illuminate\Support\Collection<\App\Models\Activity>
     */
    public static function getByType(ActivityType $type)
    {
        return Activity::where('type', $type)->get()->sortBy('start');
    }


    /**
     * Generates an Excel file containing the applications for the current activity.
     *
     * This method creates an Excel file with two sheets:
     * - "Aanmeldingen": Lists all active and pending applications, including user and guest details,
     *   their answers to activity questions, and payment information.
     * - "Reserves": Lists all reserve applications with similar details.
     *
     * The file is generated using PhpSpreadsheet and saved as a temporary file.
     *
     * @return array<string, string> An array with the file name and file path
     */
    public function createApplicationsExcelFile()
    {
        // Create an Excel file with the applications for the given activity
        $applications = $this->applications;
        // Activive applications
        // $activeApplications = $applications->where('status', ApplicationStatus::Active);
        $activeApplications = $applications->whereIn('status', [
            ApplicationStatus::Active,
//            ApplicationStatus::Pending,
        ]);
        // Reserve applications
        $reserveApplications = $applications->where('status', ApplicationStatus::Reserve);

        // Get the total paid amount
        // Calculate the total amount paid from all payments of active applications
        $totalPaid = $activeApplications
            // Flatten all payments from the filtered applications into a single collection
            ->flatMap(fn ($application) => $application->payments)
            // Sum the price of each payment using the getPrice() method
            ->sum(fn ($payment) => $payment->getPrice());

        // Get the application count
        // Sum the number of participants from all active applications
        $applicationCount = $activeApplications
            // Sum the 'participants' field from each application
            ->sum('participants');

        // Get the reserve application count
        // Sum the number of participants from all reserve applications
        $reserveApplicationCount = $reserveApplications
            // Sum the 'participants' field from each application
            ->sum('participants');

        $fileName = 'aanmeldingen_' . $this->id . '.xlsx';

        // Use PhpSpreadsheet to create an Excel file
        $spreadsheet = new Spreadsheet();
        // Set the active sheet index to the first sheet
        $activeSheet = $spreadsheet->getSheet(0)->setTitle('Aanmeldingen');
        // Create a second sheet for reserves
        $reserveSheet = $spreadsheet->createSheet(1)->setTitle('Reserves');

        // Set the sheet headers
        foreach ([$activeSheet, $reserveSheet] as $sheet) {
            // Add headers to the active sheet
            $sheet->setCellValue('A1', $this->title);
            $sheet->mergeCells('A1:B1');
            $sheet->getStyle('A1')->getFont()->setBold(true);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

            if ($sheet === $activeSheet) {
                $sheet->setCellValue('C1', 'Aanmeldingen: ' . $applicationCount . '/' . $this->maxParticipants);
                $sheet->setCellValue('D1', 'Totaal betaald: ' . formatPrice($totalPaid));
            } else if ($sheet === $reserveSheet) {
                $sheet->setCellValue('C1', 'Reserves: ' . $reserveApplicationCount);
            }

            $sheet->setCellValue('A2', 'Type');
            $sheet->setCellValue('B2', 'Naam');
            $sheet->setCellValue('C2', 'Email');
            $sheet->setCellValue('D2', 'Telefoonnummer');
            $sheet->setCellValue('E2', 'Opmerking');

            // Make headers for each activity question
            // Sort the questions by id to ensure the order is correct
            // This is done to make sure the answers are in the same order as the questions
            $col = 6;
            foreach ($this->questions->sortBy('id') as $question) {
                $sheet->setCellValue([$col, 2], $question->query);
                $col++;
            }

            // Remove last additional column
            $col--;

            // Make a border under the header and center the text
            $sheet->getStyle([1, 2, $col, 2])->getFont()->setBold(true);
            $sheet->getStyle([1, 2, $col, 2])->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THICK);
            $sheet->getStyle([1, 2, $col, 2])->getAlignment()->setHorizontal('center');
        }

        // Add the application data to the sheets
        foreach ([$activeApplications, $reserveApplications] as $applications) {
            // Determine sheet
            $sheet = $applications === $activeApplications ? $activeSheet : $reserveSheet;
            // Add application data to the Excel sheet
            $row = 3;
            foreach ($applications as $application) {
                // Color the row
                $sheet->getStyle([1, $row, $col, $row])->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FF96c8e6');
//                    ->getStartColor()->setARGB('FF328cbe');

                $sheet->setCellValue([1, $row], 'Lid');
                $sheet->setCellValue([2, $row], $application->user->name);
                $sheet->setCellValue([3, $row], $application->user->email);
                $sheet->setCellValue([4, $row], formatPhoneNumber($application->user->phone));
                $sheet->setCellValue([5, $row], $application->comment);

                // Add answers to the questions
                // Sort the answers by question_id
                // This is done to make sure the answers are in the same order as the questions
                $answers = $application->answers->sortBy('question_id');
                for ($i = 0; $i < count($answers); $i++) {
                    $sheet->setCellValue([6 + $i, $row], $answers[$i]->answer);
                }

                // Sets all to horizontal center
                $sheet->getStyle([1, $row, $col, 2])->getAlignment()->setHorizontal('left');

                $row++;

                // Add guest data if available
                foreach ($application->guests as $guest) {
                    // Color the row
                    $sheet->getStyle([1, $row, $col, $row])->getFill()->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFC2E8FF');
//                        ->getStartColor()->setARGB('FF96c8e6');

                    $sheet->setCellValue([1, $row], 'Gast');
                    $sheet->setCellValue([2, $row], $guest->name);
                    $sheet->setCellValue([3, $row], $guest->email);
                    $sheet->setCellValue([4, $row], formatPhoneNumber($guest->phone));

                    // Sets all to horizontal center
                    $sheet->getStyle([1, $row, $col, 2])->getAlignment()->setHorizontal('left');

                    $row++;
                }
            }
        }

        // Auto size all columns
        foreach (range(1, $col) as $column) {
            $columnLetter = Coordinate::stringFromColumnIndex($column);
            $activeSheet->getColumnDimension($columnLetter)->setAutoSize(true);
            $reserveSheet->getColumnDimension($columnLetter)->setAutoSize(true);
        }

        // Set the active sheet to the first one
        $spreadsheet->setActiveSheetIndex(0);

        // Write the Excel file to a temporary file
        $writer = new XlsxWriter($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $tempFile = tempnam(sys_get_temp_dir(), 'applications');
        $writer->save($tempFile);

        return ['fileName' => $fileName, 'filePath' => $tempFile];
    }
}
