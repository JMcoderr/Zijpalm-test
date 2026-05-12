<?php

namespace App\Livewire;

use Livewire\Component;

class UpcomingActivitiesDigestMail extends Component
{
    public ?int $batch_size = null;
    public ?int $delay = null;
    public int $recipientCount = 0;

    /**
     * English comment: load persisted digest settings if they exist so the modal shows saved values.
     */
    public function mount(): void
    {
        $settings = \App\Models\MailSetting::where('name', 'digest')->first();
        if ($settings) {
            $this->batch_size = $settings->batch_size;
            $this->delay = $settings->delay;
        }
        // Count all users
        $this->recipientCount = \App\Models\User::count();
    }

    public function render()
    {
        return view('livewire.upcoming-activities-digest-mail')->with('recipientCount', $this->recipientCount);
    }
}
