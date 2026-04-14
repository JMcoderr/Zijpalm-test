<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Throwable;

class UpcomingActivitiesDigestMail extends Component
{
    public array $errors = [];
    public string $successMessage = '';

    public function sendDigest()
    {
        $this->errors = [];
        $this->successMessage = '';

        try {
            $exitCode = Artisan::call('app:send-upcoming-activities-digest');

            if ($exitCode !== 0) {
                $this->errors = ['Mail toekomstige activiteiten kon niet worden verstuurd. Controleer de logs.'];
                return;
            }

            $this->successMessage = trim(Artisan::output()) ?: 'Mail toekomstige activiteiten is succesvol verstuurd.';
            // Clear the modal after success
            $this->dispatch('close-modal', modal: 'upcomingActivitiesDigestMailModal');
        } catch (Throwable $exception) {
            Log::error('[UpcomingActivitiesDigestMail] Mail send failed', [
                'error' => $exception->getMessage(),
            ]);

            $this->errors = ['Er is een fout opgetreden. Controleer de logs.'];
        }
    }

    public function render()
    {
        return view('livewire.upcoming-activities-digest-mail');
    }
}
