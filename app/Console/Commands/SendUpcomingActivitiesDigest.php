<?php
// This file is part of the app logic and has a short comment so it is easier to read.


namespace App\Console\Commands;

use App\ActivityType;
use App\Mail\UpcomingActivitiesDigest;
use App\Models\Activity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendUpcomingActivitiesDigest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-upcoming-activities-digest {--batch_size=} {--delay=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a mail with upcoming activities to the fixed digest recipients';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Get upcoming activities that are open for registration
        $activities = Activity::query()
            ->whereNotNull('start')
            ->where('start', '>=', now()->startOfDay())
            ->where('type', '!=', ActivityType::Cancelled)
            ->where('registrationStart', '<=', now())
            ->where('registrationEnd', '>=', now())
            ->orderBy('start')
            ->get();

        // Get activities that are currently running
        $runningActivities = Activity::query()
            ->whereNotNull('start')
            ->whereNotNull('end')
            ->where('start', '<=', now())
            ->where('end', '>=', now())
            ->whereNotIn('type', [ActivityType::Cancelled, ActivityType::Archived])
            ->orderBy('start')
            ->get();

        // If no upcoming activities, inform and exit
        if ($activities->isEmpty()) {
            $this->info('Geen toekomstige activiteiten gevonden binnen 8 weken.');
            return self::SUCCESS;
        }

        // Get valid recipient emails
        $emails = $this->recipientEmails()
            ->filter(fn (string $email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->values();

        // If no valid emails, warn and exit
        if ($emails->isEmpty()) {
            $this->warn('Geen geldige digest-ontvangers gevonden.');
            return self::SUCCESS;
        }

        // Only use values provided by the user (no fallback)
        $batchSize = $this->option('batch_size');
        $delay = $this->option('delay');
        if ($batchSize === null || $delay === null) {
            $this->error('You must provide both --batch_size and --delay options.');
            return self::FAILURE;
        }

        // Try to send the email
        try {
            Mail::to(config('mail.bestuur.address'), config('mail.bestuur.name'))
                ->send(new UpcomingActivitiesDigest($emails, $activities, $runningActivities, [
                    'batch_size' => $batchSize,
                    'delay' => $delay,
                ]));
        } catch (Throwable $exception) {
            // If sending fails, log error and return failure
            Log::error('[SendUpcomingActivitiesDigest] Mail send failed', [
                'error' => $exception->getMessage(),
                'activities' => $activities->count(),
                'running_activities' => $runningActivities->count(),
                'emails' => $emails->count(),
            ]);

            $this->error('Mail could not be sent due to a mail transport error.');
            return self::FAILURE;
        }

        // If successful, inform about the sent mail
        $this->info("Mail verzonden voor {$activities->count()} activiteiten naar {$emails->count()} ontvangers.");

        return self::SUCCESS;
    }

    // Get all user emails for the digest
    private function recipientEmails()
    {
        return \App\Models\User::pluck('email');
    }
}
