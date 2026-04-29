<?php

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
    private const DIGEST_RECIPIENTS = [
        'jordy.meijer@windesheim.nl',
        'jpieters@almere.nl',
    ];

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

        $emails = $this->recipientEmails()
            ->filter(fn (string $email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->values();

        if ($emails->isEmpty()) {
            $this->warn('Geen geldige digest-ontvangers gevonden.');
            return self::SUCCESS;
        }

        $batchSize = $this->option('batch_size') ?? config('mail.power_automate.batch_size.default', 50);
        $delay = $this->option('delay') ?? config('mail.power_automate.delay.default', 30);
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

            $this->error('Mail kon niet worden verzonden door een mailtransportfout.');
            return self::FAILURE;
        }

        $this->info("Mail verzonden voor {$activities->count()} activiteiten naar {$emails->count()} ontvangers.");

        return self::SUCCESS;
    }

    private function recipientEmails()
    {
        return collect(self::DIGEST_RECIPIENTS);
    }
}
