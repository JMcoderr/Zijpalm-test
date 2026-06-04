<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Throwable;

class UpcomingActivitiesDigestMail extends Component
{
    public array $componentErrors = [];
    public string $successMessage = '';
    public ?int $batch_size = null;
    public ?int $delay = null;
    public int $recipientCount = 0;

    /**
     * English comment: load persisted digest settings so the modal pre-fills last used values.
     */
    public function mount(): void
    {
        $settings = \App\Models\MailSetting::where('name', 'digest')->first();
        if ($settings) {
            $this->batch_size = $settings->batch_size;
            $this->delay = $settings->delay;
        }

        // Count all active users (members) to whom the digest will be sent
        $this->recipientCount = \App\Models\User::notSoftDeleted()->count();
    }

    public function sendDigest()
    {
        $this->componentErrors = [];
        $this->successMessage = '';

        try {
            $batchSize = request()->input('batch_size');
            $delay = request()->input('delay');

            if ($batchSize === null || $delay === null) {
                $this->componentErrors = ['Batch size en delay moeten worden opgegeven voordat de mail verstuurd kan worden.'];
                return;
            }

            $exitCode = Artisan::call('app:send-upcoming-activities-digest', [
                '--batch_size' => (int) $batchSize,
                '--delay' => (int) $delay,
            ]);

            if ($exitCode !== 0) {
                $this->componentErrors = ['Mail toekomstige activiteiten kon niet worden verstuurd. Controleer de logs en SMTP-configuratie.'];
                return;
            }

            $this->successMessage = trim(Artisan::output()) ?: 'Mail toekomstige activiteiten is succesvol verstuurd naar de ingestelde ontvangers.';
            // Clear the modal after success
            $this->dispatch('close-modal', modal: 'upcomingActivitiesDigestMailModal');
        } catch (Throwable $exception) {
            Log::error('[UpcomingActivitiesDigestMail] Mail send failed', [
                'error' => $exception->getMessage(),
            ]);

            $this->componentErrors = ['Er is een fout opgetreden. Controleer de logs.'];
        }
    }

    public function render()
    {
        return view('livewire.upcoming-activities-digest-mail')->with('recipientCount', $this->recipientCount);
    }
}
