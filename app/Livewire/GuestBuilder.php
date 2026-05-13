<?php
// This file is part of the app logic and has a short comment so it is easier to read.


namespace App\Livewire;

use Livewire\Component;
use Illuminate\View\ComponentAttributeBag;
use App\Models\Activity;

class GuestBuilder extends Component{
    // Array for display, count for tracking total
    public $guests = [];
    public $guestCount = 0;
    public $maxGuests = 0;
    public Activity $activity;

    // Base template to help Livewire
    public $templateGuest = [
        'id' => null,
        'firstName' => null,
        'lastName' => null,
        'phone' => null,
        'email' => null,
        'adult' => false,
    ];

    // Mount guests, in case an old value is needed for them
    public function mount(Activity $activity, $guests = []){
        // Keep a reference to the activity for guest/participant limits.
        $this->activity = $activity;

        // Initialize guest rows (for old input values after validation errors).
        $guests = $guests ?? [];
        $this->guests = collect($guests)->map(fn($item, $key) => array_merge(['id' => $key], $item))->all();
    }

    // Add a guest
    public function addGuest(){
//        dd($this->activity->participants + 20);
//        dd(count($this->activity->participants->all));
//        dd($this->activity->participants, $this->activity->participants->all->count());

        // Only allow adding guests when total participants stay below activity capacity.
//        if($this->activity->participants += count($this->guests) + 1 < $this->activity->maxParticipants){
        if(count($this->activity->participants->all) + count($this->guests) + 1 < $this->activity->maxParticipants){

            // Also respect the per-activity guest limit.
            if(count($this->guests) < $this->activity->maxGuests){
                $this->guestCount++;
                $guest = $this->templateGuest;
                $guest['id'] = $this->guestCount;
                $this->guests[] = $guest;
                $this->refreshGuestArray();
            }
        }
    }

    // Remove a guest
    public function removeGuest($guestId){
        // Remove one guest row and refresh bindings.
        $this->guests = collect($this->guests)->reject(fn($guest) => $guest['id'] == $guestId)->values()->toArray();
        $this->refreshGuestArray();
    }

    // Attribute to assign to input fields
    public function inputAttributes(int $id, string $type, string $label, string $field){
        // Build input attributes for one guest field.
        $guestId = "guests[{$id}][{$field}]";

        $attributes = [
            'id' => $guestId,
            'name' => $guestId,
            'type' => $type,
            'label' => ucfirst($label),
            'wire:model' => "guests.{$id}.{$field}",
        ];

        if($type === 'text'){
            $attributes['autocomplete'] = "guests[{$id}][{$field}]";
        }

        return new ComponentAttributeBag($attributes);
    }

    public function refreshGuestArray(){
        // Re-key guests and notify the parent component about the new guest count.
        $this->guests = collect($this->guests)->keyBy('id')->toArray();
        $this->dispatch('guestsUpdated', count($this->guests));
    }

    public function render(){
        return view('livewire.guest-builder');
    }
}
