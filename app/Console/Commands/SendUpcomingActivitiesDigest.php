<?php

namespace App\Console\Commands;

use App\ActivityType;
use App\Mail\UpcomingActivitiesDigest;
use App\Models\Activity;
use App\Models\User;
use App\UserNotifications;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendUpcomingActivitiesDigest extends Command
{
    // No forced recipients here — collect opted-in users at runtime.

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
        $activities = Activity::query()
            ->whereNotNull('start')
            ->where('start', '>=', now()->startOfDay())
            ->where('type', '!=', ActivityType::Cancelled)
            ->where('registrationStart', '<=', now())
            ->where('registrationEnd', '>=', now())
            ->orderBy('start')
            ->get();

        $runningActivities = Activity::query()
            ->whereNotNull('start')
            ->whereNotNull('end')
            ->where('start', '<=', now())
            ->where('end', '>=', now())
            ->whereNotIn('type', [ActivityType::Cancelled, ActivityType::Archived])
            ->orderBy('start')
            ->get();

        if ($activities->isEmpty()) {
            $this->info('Geen toekomstige activiteiten gevonden binnen 8 weken.');
            return self::SUCCESS;
        }

        // Collect all users who opted in for the newsletter and have a valid email.
        $emails = User::query()
            ->notSoftDeleted()
            ->get()
            ->filter(fn ($u) => $u->wantsNotification(UserNotifications::NEWSLETTER) && filter_var($u->email, FILTER_VALIDATE_EMAIL))
            ->pluck('email')
            ->unique()
            ->values();

        Log::info('[SendUpcomingActivitiesDigest] Using newsletter recipients', ['count' => $emails->count()]);

        if ($emails->isEmpty()) {
            $this->warn('Geen geldige digest-ontvangers gevonden (geen ingeschreven nieuwsbriefontvangers).');
            return self::SUCCESS;
        }

        // Only use values provided by the user (no fallback)
        $batchSize = $this->option('batch_size');
        $delay = $this->option('delay');
        // Ensure integer types for downstream consumers
        $batchSize = is_null($batchSize) ? null : (int) $batchSize;
        $delay = is_null($delay) ? null : (int) $delay;
        if ($batchSize === null || $delay === null) {
            $this->error('You must provide both --batch_size and --delay options.');
            return self::FAILURE;
        }
        try {
            Mail::to(config('mail.bestuur.address'), config('mail.bestuur.name'))
                ->send(new UpcomingActivitiesDigest($emails, $activities, $runningActivities, [
                    'batch_size' => $batchSize,
                    'delay' => $delay,
                ]));
        } catch (Throwable $exception) {
            Log::error('[SendUpcomingActivitiesDigest] Mail send failed', [
                'error' => $exception->getMessage(),
                'activities' => $activities->count(),
                'running_activities' => $runningActivities->count(),
                'emails' => $emails->count(),
            ]);

            $this->error('Mail could not be sent due to a mail transport error.');
            return self::FAILURE;
        }

        $this->info("Mail verzonden voor {$activities->count()} activiteiten naar {$emails->count()} ontvangers.");

        return self::SUCCESS;
    }

    private function recipientEmails()
    {
        return User::query()
            ->notSoftDeleted()
            ->get()
            ->filter(fn ($u) => $u->wantsNotification(UserNotifications::NEWSLETTER) && filter_var($u->email, FILTER_VALIDATE_EMAIL))
            ->pluck('email')
            ->unique()
            ->values();
    }
}
