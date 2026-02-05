{{-- PHP To contain attributes relevant in this view only --}}
@php
    // Classes that control the layout of the questions added
    $questionGridClasses = [
        //Enable grid
        'grid',
        // Base 14 columns
        'grid-cols-14',
        // Anything above the sm breakpoint will use 16 columns
        'sm:grid-cols-16'
    ];
@endphp

<div class="flex flex-col gap-y-2">
    {{-- Button to add questions --}}
    <x-zijpalm-button id="new-question-button" :attributes="$this->buttonAttributes(null, 'Vraag Toevoegen', 'default')" wireclick="newQuestion" center="horizontal"/>

    {{-- If question added --}}
    @if(!empty($questions))
        @foreach($questions as $question)
            <x-zijpalm-div id="questions[{{$question['id']}}]" :editable=false color="light" width="w-full" wire:key="questions[{{$question['id']}}]">
                {{-- Separator to separate questions --}}
                {{-- Div to include Title and Form divs together --}}
                <div @class($questionGridClasses)>
                    {{-- Button to remove a question --}}
                    <x-zijpalm-button :attributes="$this->buttonAttributes($question['id'], null, 'remove', 'before')" wireclick="removeQuestion({{$question['id']}})"/> 

                    {{-- Select the question type --}}
                    <x-input-field :attributes="$this->inputAttributes($question['id'], 'select', 'type', $questionTypes)" wirechange="updateQuestion({{$question['id']}}, $event.target.value)"/>
                    <x-input-field :attributes="$this->inputAttributes($question['id'], 'text', 'vraag')"/>

                    {{-- If it's a checkbox or number question, add price, for number, add a maximum --}}
                    @if($question['type'] == 'checkbox' || $question['type'] == 'number')
                        <x-input-field :attributes="$this->inputAttributes($question['id'], 'price', 'prijs')"/>
                        @if($question['type'] == 'number')
                            <x-input-field :attributes="$this->inputAttributes($question['id'], 'number', 'max')"/>
                        @endif
                    @endif

                    {{-- If it's a select, add a button to add options --}}
                    @if($question['type'] == 'select')
                        <x-zijpalm-button :attributes="$this->buttonAttributes($question['id'], null, 'add', 'after')" wireclick="addOption({{$question['id']}})"/>
                        
                        {{-- If a type is select, add its options (which should exist) --}}
                        @if($question['options'])
                        
                        {{-- Display every option, dynamically calculate the starting row (possible through the attribute function) --}}
                        @foreach($question['options'] as $id => $option)
                            <flux:separator class="col-span-14 sm:col-span-16" variant="subtle"/>
                                {{-- If there are more options than the 2 option minimum, show the button to remove the option --}}
                                @if(count($question['options']) > 2)
                                    <x-zijpalm-button :attributes="$this->buttonAttributes($question['id'], null, 'subtract', 'before', 'option', 'row-start-[({{$id}} += 3)] col-start-5')" wireclick="removeOption({{$question['id']}}, {{$id}})"/>
                                @endif
                                <x-input-field :attributes="$this->inputAttributes($question['id'], 'option', 'optie', null, $id)"/>
                                <x-input-field :attributes="$this->inputAttributes($question['id'], 'option-price', 'prijs', null, $id)"/>
                            @endforeach
                        @endif                        
                    @endif
                </div>
            </x-zijpalm-div>
        @endforeach
    @endif
</div>