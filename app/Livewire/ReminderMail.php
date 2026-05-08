<?php

namespace App\Livewire;

use App\Models\Activity;
use Livewire\Component;

class ReminderMail extends Component
{
    public Activity $activity;
    public array $errors = [];
    public ?int $batch_size = null;
    public ?int $delay = null;
    public int $recipientCount = 0;

    /**
     * English comment: mount the component and load persisted settings if available.
     */
    public function mount(): void
    {
        $settings = \App\Models\MailSetting::where('name', 'reminder')->first();
        if ($settings) {
            $this->batch_size = $settings->batch_size;
            $this->delay = $settings->delay;
        }

        // Count non-cancelled, non-reserved participants
        $this->recipientCount = $this->activity->applications
            ->whereNotIn('status', [\App\ApplicationStatus::Cancelled, \App\ApplicationStatus::Reserve])
            ->pluck('email')
            ->unique()
            ->count();
    }

    public function render()
    {
        return view('livewire.reminder-mail')->with('recipientCount', $this->recipientCount);
    }
}
