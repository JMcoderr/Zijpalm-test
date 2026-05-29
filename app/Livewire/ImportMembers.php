<?php
// This file is part of the app logic and has a short comment so it is easier to read.


namespace App\Livewire;

use Livewire\Component;

class ImportMembers extends Component
{
    public string $id;
    public string $endpoint;
    public array $errors = [];

    public function render()
    {
        return view('livewire.import-members');
    }
}
