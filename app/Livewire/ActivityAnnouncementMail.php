<?php

namespace App\Livewire;

use App\Models\Activity;
use Livewire\Component;

class ActivityAnnouncementMail extends Component
{
    public Activity $activity;
    public array $errors = [];

    public function render()
    {
        return view('livewire.activity-announcement-mail');
    }
}
