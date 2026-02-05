<?php

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
        $this->activity = $activity;

        $guests = $guests ?? [];
        $this->guests = collect($guests)->map(fn($item, $key) => array_merge(['id' => $key], $item))->all();
    }

    // Add a guest
    public function addGuest(){
//        dd($this->activity->participants + 20);
//        dd(count($this->activity->participants->all));
//        dd($this->activity->participants, $this->activity->participants->all->count());

        // If the current participants + current guests + 1 (the user) is all below the maxParticipants, continue
//        if($this->activity->participants += count($this->guests) + 1 < $this->activity->maxParticipants){
        if(count($this->activity->participants->all) + count($this->guests) + 1 < $this->activity->maxParticipants){

            // If the current amount of guests is below the maxGuests for an activity
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
        $this->guests = collect($this->guests)->reject(fn($guest) => $guest['id'] == $guestId)->values()->toArray();
        $this->refreshGuestArray();
    }

    // Attribute to assign to input fields
    public function inputAttributes(int $id, string $type, string $label, string $field){
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
        $this->guests = collect($this->guests)->keyBy('id')->toArray();
        $this->dispatch('guestsUpdated', count($this->guests));
    }

    public function render(){
        return view('livewire.guest-builder');
    }
}
