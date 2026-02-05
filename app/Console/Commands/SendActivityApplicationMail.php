<?php

namespace App\Console\Commands;

use App\ActivityType;
use App\Mail\ActivityApplications;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendActivityApplicationMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-activity-application-mail';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send an email with an excel sheet containing all applications for an activity to the board when the end of the application period is reached';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get all activities that have reached the end of their cancellation period in the last hour
        // and are not cancelled
        $activities = Activity::whereBetween('cancellationEnd', [now()->subDay(), now()])
            ->where('type', '!=', ActivityType::Cancelled)
            ->get();

        if ($activities->isEmpty()) {
            $this->info("No activities have reached the end of their cancellation period in the last hour.");
            return;
        }

        $activities->each(function (Activity $activity) {
            // Create an excel file
            $excelFile = $activity->createApplicationsExcelFile();

            // Send the email to the board with the excel file attached
            Mail::to(config('mail.bestuur.address'), config('mail.bestuur.name'))->send(new ActivityApplications(User::find(1), $activity, $excelFile));

            // Remove the temp file after sending the email
            if (file_exists($excelFile['filePath'])) {
                unlink($excelFile['filePath']);
            }

            $this->info('Sent activity applications email for activity: ' . $activity->title);
        });

    }
}
