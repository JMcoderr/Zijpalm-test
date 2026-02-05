<?php

namespace App\Livewire;

use App\Models\Activity;
use Livewire\Component;

class ReminderMail extends Component
{
    public Activity $activity;
    public array $errors = [];

    public function render()
    {
        return view('livewire.reminder-mail');
    }
}
