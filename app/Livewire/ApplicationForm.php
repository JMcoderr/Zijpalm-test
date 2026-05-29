<?php
// This file is part of the app logic and has a short comment so it is easier to read.


namespace App\Livewire;

use Livewire\Component;
use App\Models\Activity;
use Illuminate\View\ComponentAttributeBag;

class ApplicationForm extends Component{
    // Listen for guests being added or removed
    protected $listeners = [
        'guestsUpdated' => 'updateParticipants',
    ];

    // The activity featured in the form
    public Activity $activity;
    public array $errors = [];

    // The questions for the activity
    public $questions;

    // The base, options and total costs for display
    public $costs = [
        'base' => 0,
        'options' => 0,
        'total' => 0,
    ];

    // Participant count for this application, default 1 (the User)
    public $participants = 1;

    public function mount(Activity $activity){
        // Store the activity so all calculations can use the same source data.
        $this->activity = $activity;

        // Map question models to a lightweight array structure used by the Livewire form.
        $this->questions = $activity->questions->map(
            fn($question) => [
                'id' => $question->id,
                'type' => $question->type->value,
                'label' => $question->query,
                'price' => (float)$question->price,
                'max' => $question->max_amount,
                'options' => $question->allOptions(),
                'required' => $question->type->value !== 'checkbox',
                'value' => null,
            ]
        );

        // Calculate the base cost once on load.
        $this->updateBaseCost();
    }

    // Simple return for a question based on ID
    public function getQuestion($questionId){
        return $this->questions->where('id', $questionId)->first();
    }

    // Get the answer (value) for a question, if the question has options, return the selected option
    public function getAnswer($questionId){
        // For select questions return the selected option object, otherwise the raw value.
        $question = $this->getQuestion($questionId);
        return ($question['options'] ? collect($question['options'])->firstWhere('option', $question['value']) : $question['value']);
    }

    // Whenever a participant is added or removed from the GuestBuilder component
    public function updateParticipants($guestCount){
        // Count the user plus all added guests.
        $this->participants = max(1, (int) $guestCount + 1);
        $this->updateBaseCost();
    }

    // Update the base cost, based on the activity's price and the number of participants
    public function updateBaseCost(){
        // Check if the current user is an organizer and still qualifies for a free organizer spot.
        $isOrganizer = false;
        if (auth()->user() && $this->activity->organizer) {
            $isOrganizer = str_contains($this->activity->organizer, auth()->user()->name);
        }
        $activeFreeOrganizers = $this->activity->applications
            ->where('status', \App\ApplicationStatus::Active)
            ->filter(function($app) {
                return str_contains($this->activity->organizer, $app->user->name);
            })->count();
        $canRegisterFree = $isOrganizer && ($activeFreeOrganizers < $this->activity->free_organizer_count);

        // If the organizer comes alone, base price is free.
        if($canRegisterFree && $this->participants == 1) {
            $this->costs['base'] = 0;
        } elseif($canRegisterFree && $this->participants > 1) {
            // If organizer is free, only guests count towards base cost.
            $guestCount = $this->participants - 1;
            $this->costs['base'] = max(0, $guestCount) * $this->activity->price;
        } else {
            // Otherwise everyone pays the normal base price.
            $this->costs['base'] = $this->activity->price * $this->participants;
        }
        $this->updateTotalCost();
    }

    // Update the options cost whenever a question is updated
    public function updateOptionsCost(){
        // Sum all extra option costs from answered questions.
        $this->costs['options'] = $this->questions->sum(
            function($question){
                // If the question is of the text type or empty, return 0 in the sum
                if($question['type'] === 'text' || $question['value'] === null){
                    return;
                }
                // For select inputs use option price, otherwise use question price.
                $option = collect($question['options'])->firstWhere('option', $question['value']);
                $price = $option ? (float)$option['price'] : (float)$question['price'];
                return $price * (($option || $question['value'] === true) ? 1 : (int)$question['value']);
            }
        );
        $this->updateTotalCost();
    }

    // Update the total cost, which is base cost + options cost
    public function updateTotalCost(){
        $this->costs['total'] = $this->costs['base'] + $this->costs['options'];
    }

    // Function to easily assign the questions attributes
    public function questionAttributes($question){
        // Build dynamic input attributes for each question component.
        return new ComponentAttributeBag(
                array_filter([
                    'id' => "questions[{$question['id']}]",
                    'type' => $question['type'],
                    'label' => $question['label'] . ($question['price'] != 0 ? ' ('. formatPrice($question['price']).')' : ''),
                    'price' => $question['price'],
                    'max' => $question['max'],
                    'options' => $question['options'],
                    'required' => $question['required'],
                    'wiremodel' => "questions.{$this->questions->search($question)}.value",
                    'wirechange' => "updateOptionsCost()",
            ],
                // Filter out empty values
                function($value){
                    return !is_null($value) && $value !== '' && $value !== false && $value !== [] || $value === 0;
                }
            )
        );
    }

    public function render(){
        // Render the form component and expose helper flags for the Blade view.
        return view('livewire.application-form', [
            // Pass a flag to the view to show '€,-' if the organizer is free
            'showFreeOrganizerBase' => ($canRegisterFree ?? false)
        ]);
    }
}
