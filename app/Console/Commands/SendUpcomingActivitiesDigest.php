<?php

namespace App\Console\Commands;

use App\ActivityType;
use App\Mail\UpcomingActivitiesDigest;
use App\Models\Activity;
use App\Models\User;
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
    protected $signature = 'app:send-upcoming-activities-digest';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a digest with upcoming activities to all active members';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $activities = Activity::query()
            ->whereNotNull('start')
            ->where('start', '>=', now()->startOfDay())
            ->where('start', '<=', now()->addWeeks(8)->endOfDay())
            ->where('type', '!=', ActivityType::Cancelled)
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

        $emails = User::notSoftDeleted()
            ->whereNotNull('email')
            ->pluck('email')
            ->filter(fn (string $email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->values();

        if ($emails->isEmpty()) {
            $this->warn('Geen actieve leden met geldig e-mailadres gevonden.');
            return self::SUCCESS;
        }

        try {
            Mail::to(config('mail.bestuur.address'), config('mail.bestuur.name'))
                ->send(new UpcomingActivitiesDigest($emails, $activities, $runningActivities));
        } catch (Throwable $exception) {
            Log::error('[SendUpcomingActivitiesDigest] Mail send failed', [
                'error' => $exception->getMessage(),
                'activities' => $activities->count(),
                'running_activities' => $runningActivities->count(),
                'emails' => $emails->count(),
            ]);

            $this->error('Digest kon niet worden verzonden door een mailtransportfout.');
            return self::FAILURE;
        }

        $this->info("Digest verzonden voor {$activities->count()} activiteiten naar {$emails->count()} leden.");

        return self::SUCCESS;
    }
}
