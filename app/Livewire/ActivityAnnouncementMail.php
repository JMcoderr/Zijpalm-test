<?php

namespace App\Livewire;

use App\Models\Activity;
use Livewire\Component;

class ActivityAnnouncementMail extends Component
{
    public Activity $activity;
    public array $errors = [];
    public ?int $batch_size = null;
    public ?int $delay = null;
    public int $recipientCount = 0;

    /**
     * English comment: load persisted announcement settings if they exist so the modal shows saved values.
     */
    public function mount(): void
    {
        $settings = \App\Models\MailSetting::where('name', 'announcement')->first();
        if ($settings) {
            $this->batch_size = $settings->batch_size;
            $this->delay = $settings->delay;
        }

        // Count users who are not participants (for announcement to members)
        $participantIds = $this->activity->applications
            ->whereNotIn('status', [\App\ApplicationStatus::Cancelled, \App\ApplicationStatus::Reserve])
            ->pluck('user_id')
            ->unique()
            ->toArray();
        $this->recipientCount = \App\Models\User::whereNotIn('id', $participantIds)->count();
    }

    public function render()
    {
        return view('livewire.activity-announcement-mail')->with('recipientCount', $this->recipientCount);
    }
}
