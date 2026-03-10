<?php

use App\Mail\ActivityApplied;
use App\Models\Activity;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:test-activity-confirmation-mail {activity : The activity id} {email=test@local.test : The recipient email} {--mode=both : standard, personal, or both}', function (string $activity, string $email) {
    $activityModel = Activity::with(['applications.user', 'applications.answers.question', 'applications.guests'])->findOrFail($activity);
    $application = $activityModel->applications->first();

    if (!$application || !$application->user) {
        $this->error('Deze activiteit heeft geen aanmelding met gekoppelde gebruiker om te testen.');
        return self::FAILURE;
    }

    $mode = strtolower((string) $this->option('mode'));

    if (!in_array($mode, ['standard', 'personal', 'both'], true)) {
        $this->error('Ongeldige mode. Gebruik standard, personal of both.');
        return self::FAILURE;
    }

    if (in_array($mode, ['standard', 'both'], true)) {
        $standardActivity = $activityModel->replicate();
        $standardActivity->id = $activityModel->id;
        $standardActivity->setRelation('applications', $activityModel->applications);
        $standardActivity->personal_confirmation_enabled = false;
        $standardActivity->personal_confirmation = null;

        Mail::to($email)->send(new ActivityApplied($standardActivity, $application->user));
        $this->info('Standaard bevestigingsmail verstuurd naar ' . $email);
    }

    if (in_array($mode, ['personal', 'both'], true)) {
        if (blank($activityModel->personal_confirmation)) {
            $this->warn('Deze activiteit heeft nog geen persoonlijke bevestigingstekst.');
        }

        $personalActivity = $activityModel->replicate();
        $personalActivity->id = $activityModel->id;
        $personalActivity->setRelation('applications', $activityModel->applications);
        $personalActivity->personal_confirmation_enabled = true;
        $personalActivity->personal_confirmation = $activityModel->personal_confirmation;

        Mail::to($email)->send(new ActivityApplied($personalActivity, $application->user));
        $this->info('Persoonlijke bevestigingsmail verstuurd naar ' . $email);
    }

    return self::SUCCESS;
})->purpose('Send a standard and/or personal activity confirmation mail for testing.');

// Schedule commands
Schedule::command('app:send-activity-application-mail')->dailyAt('06:00');

// Work the queue every minute, but only if there are jobs in the queue
// The queue is used for sending emails
Schedule::command('queue:work --stop-when-empty')->everyMinute()->withoutOverlapping();