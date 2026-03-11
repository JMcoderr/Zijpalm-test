<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes;
use App\QuestionType;
use Illuminate\View\ComponentAttributeBag;
use Livewire\Attributes\On;

class QuestionBuilder extends Component{
    // Total questions made
    public $questionCount = 0;

    // Questions to render
    public $questions = [];

    // Initialize questionTypes as an empty array
    public $questionTypes = [];

    // Question template, used for adding new questions and helps livewire to know what to expect
    public $templateQuestion = [
        'id' => null,  
        'type' => 'text',
        'select' => null,
        'text' => null,
        'price' => null,
        'number' => null,
        'options' => [
            0 => [
                'option' => null,
                'price' => null,
            ],
            1 => [
                'option' => null,
                'price' => null
            ],
        ],
    ];

    // Translation tabel to display Dutch words.
    // public $fieldTypes = [
    //     'type' => 'Type',
    //     'vraag' => 'Vraag',
    //     '' => '',
    // ];
    
    // Used to properly initialize things on page load
    public function mount($questions){
        // Stores the case label/value pairs
        $this->questionTypes = QuestionType::labelledCases();

        // If questions are given, assign them to the array and refresh
        if($questions){
            $this->questions = collect($questions)->map(function($item, $key) {
                if(is_array($item)){
                    return array_merge(['id' => $key], $item);
                }
                // Map Question model to the array structure the blade expects
                return [
                    'id'      => $key,
                    'type'    => $item->type instanceof \BackedEnum ? $item->type->value : $item->type,
                    'vraag'   => $item->query,
                    'prijs'   => $item->price,
                    'max'     => $item->max_amount,
                    'options' => $item->selectOptions->map(fn($o) => [
                        'optie' => $o->option,
                        'prijs' => $o->price,
                    ])->toArray(),
                ];
            })->all();
            // dd($this->questions);
        }
    }

    // Function to generate the attributes for the input fields, based on the question type
    public function inputAttributes(int $id, string $type, string $field, array $options = null, int $optionId = null){
        // Generate an unique it for the question's fields, unless its a placeholder
        $questionId = "questions[{$id}][{$field}]";

        // If an optionId is 0 or not null
        // Not known as to why this assignment breaks for the first option without adding the '$optionId === 0' check, but best not remove it
        if($optionId === 0 || $optionId != null){
            $questionId = "questions[{$id}][options][{$optionId}][{$field}]";
        }

        // Attributes to assign to the field
        $questionAttributes = [
            'id' => $questionId,
            'name' => $questionId,
            'label' => ucfirst($field),
            'type' => $type,
            'questionBuilder' => true,
        ];

        if($type === 'text'){
            $questionAttributes['span'] = 'col-span-4 sm:col-span-6';
        }

        // If given type is select, and options are given
        if($type === 'select' && !empty($options)){
            $questionAttributes['options'] = $options;
            $questionAttributes['span'] = 'col-span-4';
        }

        // If given type is number, and the input is for 'Max'
        if($type === 'number' && $field === 'max'){
            $questionAttributes['min'] = 2;
            $questionAttributes['span'] = 'col-span-2';
        }

        // If the type is price, or ends in price
        if($type === 'price'){
            $questionAttributes['span'] = 'col-span-3';
        }

        // If the type is option or contains 'option'
        if($type === 'option' || str_contains($type, 'option')){
            // If its 1:1 with 'option'
            if($type === 'option'){
                $questionAttributes['type'] = 'text';
                $questionAttributes['span'] = 'col-start-6 col-span-6';
            }

            // If it's an option's price field
            if(str_contains($type, 'price')){
                $questionAttributes['type'] = 'price';
                $questionAttributes['span'] = 'col-span-3';
            }

            // If somehow, an optionId is missing, make it 0
            if(!$optionId){
                $optionId = 0;
            }

            // Always add the wiremodel
            $questionAttributes['wiremodel'] = "questions.{$id}.options.{$optionId}.{$field}";
        }

        // If any type but option, make sure it doesn't contain any 'option'
        if($type != 'option' && !str_contains($type, 'option')){
            $questionAttributes['wiremodel'] = "questions.{$id}.{$field}";
        }

        // Return the attributes as a new ComponentAttributeBag, as plain arrays will explode on ->merge()
        return new ComponentAttributeBag($questionAttributes);
    }

    // Function to generate the attributes for the buttons, based on their functionality
    public function buttonAttributes(int $id = null, string $label = null, string $variant,  string $placement = null, string $inputType = null, string $span = null, string $size = null){
        // If an id is given, generate a unique id for the button
        if($id !== null){
            if($inputType === 'option'){
                $buttonId = "question[{{$id}}]{{$variant}}optionbutton";
            }
            else{
                $buttonId = "question[{$id}]{{$variant}}button";
            }
        }

        // Attributes to assign to the button
        $buttonAttributes = [
            'type' => 'action',
            'variant' => $variant,
        ];

        if($placement === 'before'){
            $buttonAttributes['margin'] = 'mt-auto mb-2 sm:mb-1.5 me-0.5 ms-auto';
        }

        if($placement === 'after'){
            $buttonAttributes['margin'] = 'mt-auto mb-2 sm:mb-1.5 ms-0.5 me-auto';
        }

        // If the button is not default / labeled
        if($variant != 'default'){
            if(!$span){
                $buttonAttributes['span'] = 'col-span-1';
            }
            if(!$size){
                $buttonAttributes['size'] = 'size-5 sm:size-6';
            }
        }

        if($span){
            $buttonAttributes['span'] = $span;
        }

        if(isset($buttonId)){
            $buttonAttributes['id'] = $buttonId;
        }

        if($label){
            $buttonAttributes['label'] = $label;
        }

        return new ComponentAttributeBag($buttonAttributes);
    }

    public function getQuestion($questionId){
        if(in_array($this->questions[$questionId])){
            return $this->questions[$questionId];
        }
        else{
            throw new Exception("No such question found");
        }
    }

    // Adding questions
    public function newQuestion(){
        // Increment question count
        $this->questionCount++;
        
        // Base on template
        $question = $this->templateQuestion;

        // Base id on question count
        $question['id'] = $this->questionCount;

        // Place the question in the array of questions
        $this->questions[] = $question;

        $this->refreshQuestionsArray();
    }

    // Function to set the question type
    public function setQuestionType($type){
        return collect($this->questionTypes)->firstWhere('type', $type)['type'];
    }
    
    // Updating the question
    public function updateQuestion($questionId, $type){
        $this->questions[$questionId]['type'] = $this->setQuestionType($type);
    }

    // Removing questions
    public function removeQuestion($questionId){
        // Remove the question from the array
        unset($this->questions[$questionId]);
    }

    // Add option
    public function addOption($questionId){
        $this->questions[$questionId]['options'][] = ['id' => count($this->questions[$questionId]['options'])];
    }

    // Remove option
    public function removeOption($questionId, $optionId){
        // Only remove if there's 2 or more options
        if(count($this->questions[$questionId]['options']) > 2){
            // Remove the option from the array
            unset($this->questions[$questionId]['options'][$optionId]);
            $this->refreshQuestionsArray();
        }
    }

    public function refreshQuestionsArray(){
        $this->questions = collect($this->questions)->keyBy('id')->toArray();
    }

    // Function to display the questions
    public function render(){
        return view('livewire.question-builder');
    }
}