@props([
    // Input Field Content props
    'id' => null,
    'name' => null,
    'information' => null,
    'label' => null,
    'type' => null,
    'price' => null,
    'min' => null,
    'max' => null,
    'step' => null,
    'pattern' => null,
    'options' => [],
    'required' => false,
    'action' => null,
    'onchange' => null,
    'wiremodel' => null,
    'wirechange' => null,
    'value' => null,
    'autocomplete' => 'off',
    'hidden' => false,
    'disabled' => false,
    'fakedisabled' => false,
    'questionBuilder' => false,
    'accept' => null,
    'readonly' => null,
    'tooltip' => null,
    'checked' => null,
    'inputmode' => null,
    'optionOnly' => false,
    'optionValuePair' => false,
    'selected' => null,

    // Input Field Style props
    'height' => 'h-full',
    'width' => 'w-full',
    'margin' => 'mx-auto',
    'padding' => 'p-2',
    'span' => 'cols-span-1',
    'fontWeight' => 'font-normal',
    'background' => 'bg-zinc-100',
    'textColor' => 'text-black',
    'flexDirection' => 'flex-col',
    'labelPosition' => 'justify-start',
    'textPosition' => 'text-start',

    // Optional
    'drawBoxes' => false,
])

{{-- Additional component functions --}}
@vite('resources/js/component-addons/toggle-tooltips.js')

@php
    // Classes for the <div> wrapping the label and input
    $inputFieldClasses = [
        'font-bold',
        'flex',
        $flexDirection,
        $height,
        $width,
        $margin,
        $padding,
        $span,
    ];

    // Classes for the inputs themselves
    $inputClasses = [
        $fontWeight,
        'rounded-sm',
        'shadow-sm',
        'px-1',
        'focus:outline-0',
        'h-7',
        $textPosition,
        'w-full',
    ];

    // Classes for the labels
    $labelClasses = [
        'flex',
        $labelPosition,
    ];

    // Allowed input types
    $types = [
        'text',
        'number',
        'price',
        'date',
        'time',
        'datetime-local',
        'checkbox',
        'select',
        'file',
        'editor',
        'radio',
        'phone',
    ];

    // If type is price, make it a text input with a pattern
    if($type === 'price'){
        $type = 'text';
        $pattern = "^\d+([.,]\d{2})?$";
    }

    // If the type is a number, don't go under 0 (it doesn't make sense, ever.)
    if($type === 'number'){
        $min = 0;
    }

    // Sort options, options without price alphabetically first, options with price alphabetically after
    // if($type === 'select' && $options){
    //     $options = collect($options)->sortByDesc(fn($option) => [!$option, $option])->toArray();
    // }

    // If the key does not exist and the slot isn't filled with anything
    if(!array_key_exists($type, $types) && $slot == null){
        throw new Exception("Invalid Input Field type");
    }

    // If no id is given
    if(!$id){
        throw new Exception("Input Field Requires an ID");
    }

    // Hides the whole div
    if($hidden){
        array_push($inputFieldClasses, 'hidden');
    }

    if($background){
        if($disabled){
            $background = 'bg-zinc-300';
            $textColor = 'text-zinc-500';
        }
        array_push($inputClasses, $background, $textColor);
    }

    if($type === 'checkbox'){
        array_push($inputFieldClasses, 'justify-end');
    }

    // Adds a flex-1 to phone inputs so they take the full width after "06"
    if ($type === 'phone') {
        array_push($inputClasses, 'flex-grow min-w-0');
    }

    // Merge input field attributes, currently just classes
    $inputFieldAttributes = $attributes->merge([
        'class' => implode(' ', $inputFieldClasses),
        'id' => $id . (ctype_alpha(substr($id, -1)) ? '-' : '') . 'wrapper',
        'x-data' => $tooltip ? '{showTooltip: false}' : null,
    ]);

    // Combines any attributes, including classes, but also things like min/max for number inputs and options for select inputs
    $inputAttributes = $attributes->merge(
        array_filter([
            'class' => implode(' ', $inputClasses),
            'id' => $id,
            'name' => $id,
            'information' => $information,
            'type' => $type,
            'pattern' => $pattern,
            'min' => $min,
            'max' => $max,
            'required' => $required,
            'price' => $price,
            'action' => $action,
            'onchange' => $action,
            'wire:model' => $wiremodel,
            'wire:change' => $wirechange,
            'value' => old($id) ? old($id) : $value,
            'autocomplete' => $autocomplete,
            'disabled' => $disabled,
            'readonly' => $readonly,
            'x-on:focus' => $tooltip ? 'showTooltip = true' : null,
            'x-on:blur' => $tooltip ? 'showTooltip = false' : null,
            'checked' => $checked,
            'inputmode' => $inputmode,
        ],
            // Callback function, fallback option if a value is null/empty
            function($keyValue){
                // Reject if a value isn't null, isn't an empty string, isn't an empty array
                return !is_null($keyValue) && ($keyValue !== '' && $keyValue !== null && $keyValue !== false) || $keyValue === 0;
            }
        )
    );

    // Fake disabled so field value can still be used on form submits
    if($fakedisabled){
        $inputAttributes = $inputAttributes->merge(['class' => implode(' ', ['bg-zinc-300 text-zinc-500 pointer-events-none']), 'readonly' => true]);
    }

    $checkBoxBackground = 'bg-linear-to-t from-zijpalm-400 to-zijpalm-200';
    // If the type is checkbox and disabled is true, make the background grey
    if($type == 'checkbox' && $disabled){
        $checkBoxBackground = 'bg-linear-to-t from-zinc-500 to-zinc-300';
    }

    // These have gotten out of hand, so I'm putting them in a separate array
    $checkBoxClasses = [
        'checkbox' => [
            // Defaults
            'size-6',
            'min-h-6',
            'min-w-6',
            'peer',
            'appearance-none',
            'bg-zinc-300',
            'border',
            'border-[rgba(0,0,0,0.15)]',
            'rounded-md',
            'shadow-sm',
            // Transformations and transitions
            'transform',
            'outline-0',
            // When checked
            'checked:appearance-none',
            'checked:duration-50',
            // When not checked
            'not-checked:duration-200',
            'not-checked:hover:scale-110',
            'not-checked:focus:border-2',
            'not-checked:focus:scale-110',
            'not-checked:focus:border-zijpalm-200',
        ],
        'checkbox-label' => [
            'absolute',
            'hidden',
            'peer-checked:block',
            'peer-checked:scale-100',
            'peer-checked:hover:scale-110',
            'duration-200',
            'z-1',
        ],
        'checkbox-icon' => [
            // 'bg-linear-to-t',
            // 'from-zijpalm-400',
            // 'to-zijpalm-200',
            $checkBoxBackground,
            'inset-shadow-zijpalm-100',
            'text-zinc-100',
            'rounded-md',
            'stroke-4',
            'shadow-sm',
            'duration-200',
        ],
    ];

    // Assign the classes and attributes to checkbox attributes, and arrays for just classes
    $checkBoxAttributes = $inputAttributes->except(['class', 'action'])->merge(['class' => implode(' ', $checkBoxClasses['checkbox']), 'onchange' => $action]);
    $checkBoxIconClasses = implode(' ', $checkBoxClasses['checkbox-icon']);
    $checkBoxLabelClasses = implode(' ', $checkBoxClasses['checkbox-label']);

    // Generate radio attributes if the type is radio
    if ($type == 'radio') {
        $radioAttributes = $attributes->merge(
            array_filter([
                'name' => $id,
                'type' => $type,
                'required' => $required,
                'onchange' => $action,
                'wire:model' => $wiremodel,
                'wire:change' => $wirechange,
            ],
                // Callback function, fallback option if a value is null/empty
                function($keyValue){
                    // Reject if a value isn't null, isn't an empty string, isn't an empty array
                    return !is_null($keyValue) && ($keyValue !== '' && $keyValue !== null && $keyValue !== false) || $keyValue === 0;
                }
            )
        );
    }

    // If the type is a phone number, add the pattern and maxlength attributes
    if ($type === 'phone') {
        $phoneAttributes = $inputAttributes->merge(['pattern' => '\d{8}', 'maxlength' => '8', 'inputmode' => 'numeric', 'type' => 'tel']);
    }

    // Commented out to globally disable, the variable does still exist, but is only used during debugging
    // if($drawBoxes){
    //     array_push($inputFieldClasses, 'border', 'border-black', 'bg-lime-200');
    //     array_push($inputClasses, 'border', 'border-black');
    // }
@endphp

{{-- Scripts to load per input field --}}
<script>
    document.addEventListener(
        'DOMContentLoaded',
        function(){
            if(@json($type) === 'editor'){
                toggleRequiredTooltip(document.getElementById('editorjs-data'));
            }
            else{
                toggleRequiredTooltip(document.getElementById(@json($id)));
            }
        }
    );
</script>

{{-- Add inputFieldClasses to the class attribute and allow other attributes to be passed on --}}
<div {{$inputFieldAttributes}}>
    {{-- If type is anything but 'check', add a label --}}
    @if($type != 'checkbox')
        <label @if($type != 'file') for="{{$id}}" @endif @class($labelClasses)>
            <p class="truncate"> {{$label}} </p>
            {{-- If the input is required, append a disgusting looking red asterisk to the label --}}
            <flux:tooltip id="{{$id}}tooltip" content="Verplicht Veld" @class(['hidden' => !$required])>
                {{-- If the input is required, append a disgusting looking red asterisk to the label --}}
                <p class="text-red-500 font-black ps-0.5">*</p>
            </flux:tooltip>
            {{-- If the input requires more info, add an information icon --}}
            @if($information)
                <flux:tooltip id="{{$id}}tooltip" toggleable interactive>
                    {{-- The button is square, otherwise, even if it's a circle, it'll be very wide --}}
                    <flux:button variant="ghost" class="rounded-full! size-min!" square>
                        {{-- Icon is split to allow for more customization --}}
                        <flux:icon.exclamation-circle variant="micro" class="cursor-pointer fill-zijpalm-500 hover:fill-zijpalm-400 duration-200 hover:scale-110 stroke-zinc-100"/>
                    </flux:button>
                    {{-- Override default tooltip content background --}}
                    <flux:tooltip.content class="bg-transparent! p-0! size-min! overflow-visible!">
                        <livewire:info-pop-up :info="$information">
                    </flux:tooltip.content>
                </flux:tooltip>
            @endif
        </label>
    @endif

    {{-- If it is readonly, fake the input and force slot data into it --}}
    {{-- @if($readonly)
        <span {{$inputAttributes}}>{{$slot}}</span>
    @endif --}}

    <div class="relative size-full">
    {{-- If a tooltip is given, display it with the given content --}}
    @if($tooltip)
        <x-zijpalm-div :editable="false" x-show="showTooltip" text="{{$tooltip}}" display="absolute" width="max-w-[95%]" margin="mx-5" padding="py-1 px-2" class="top-8 z-50"/>
    @endif

    {{-- If the input doesn't require additional lines and no $slot item is given --}}
    @if($type != 'checkbox' && $type != 'select' && $type != 'file' && $type != 'editor' && $slot->isEmpty() && $type != 'radio' && $type != 'phone')
        <input {{$inputAttributes}}>
    @endif

    {{-- If type is checkbox --}}
    @if($type == 'checkbox')
        <div class="flex flex-col justify-end size-full">
            <div class="flex gap-x-1.5 sm:mb-0.5 items-end">
                <input {{$checkBoxAttributes}}>
                <label for="{{$id}}" @class($checkBoxLabelClasses)>
                    <flux:icon.check @class($checkBoxIconClasses)/>
                </label>
                <label class="flex">
                    <p class="line-clamp-2 break-words hyphens-auto">{{$label}}</p>
                    <flux:tooltip id="{{$id}}tooltip" content="Verplicht Veld" @class(['hidden' => !$required])>
                        <p class="text-red-500 font-black ps-0.5">*</p>
                    </flux:tooltip>
                </label>
            </div>
        </div>
    @endif

    {{-- Select Input, only works with an array of options --}}
    @if($type == 'select' && $options)
        <select {{$inputAttributes}}>
            @if(!$questionBuilder)
                <option> - </option>
            @endif
            @if($questionBuilder)
                @foreach ($options as $option)
                    <option value="{{$option['type']}}">{{$option['label']}}</option>
                @endforeach
            @elseif($optionOnly)
                @foreach ($options as $option)
                    <option class="flex justify-between" value="{{$option}}" {{$selected == $option ? 'selected' : ''}}>{{ucfirst($option)}}</option>
                @endforeach
            @elseif($optionValuePair)
                @foreach ($options as $option)
                    <option class="flex justify-between" value="{{$option['id']}}" {{$selected == $option['id'] ? 'selected' : ''}}>{{ucfirst($option['option'])}}</option>
                @endforeach
            @else
                @foreach ($options as $option)
                    <option class="flex justify-between" id="options[{{$option['id']}}]" value="{{$option['option']}}" price="{{$option['price'] ?? 0.00}}">{{$option['option']}} {{$option['price'] != 0.00 ? '('.formatPrice($option['price']).')' : ''}}</option>
                @endforeach
            @endif
        </select>
    @endif

    @if($type == 'file')
        @php
            array_push($inputClasses, 'flex', 'justify-between', 'pe-0');
        @endphp
        <div>
            <input
                id="{{$id}}"
                name="{{$id}}"
                type="file"
                @if($wiremodel) wire:model="{{$wiremodel}}" @endif
                class="opacity-0 absolute z-0 w-0"
                accept="{{$accept}}"
                onchange="document.getElementById('{{$id}}-file-name').textContent = this.files.length ? this.files[0].name : 'Kies een {{lcfirst($label)}}'; {{$action}}"
                @if($required) required @endif
            >
{{--            <input id="{{$id}}" name="{{$id}}" type="file" @if($wiremodel) wire:model="{{$wiremodel}}" @endif class="opacity-0 absolute z-0 w-0" accept="{{$accept}}" onchange="{{$action}}" @if($required) required @endif >--}}
            <div
                {{$attributes->merge(['class' => implode(' ', $inputClasses)])}}
                onclick="document.getElementById('{{$id}}').click()"
                style="cursor: pointer;"
            >
                <span id="{{$id}}-file-name" class="truncate text-zinc-500 my-auto"> Kies een {{lcfirst($label)}} </span>
                <button class="group/file duration-300 transition-colors cursor-pointer bg-linear-to-t from-zijpalm-400 to-zijpalm-200 inset-shadow-zijpalm-100 border-l border-[rgba(0,0,0,0.55)] rounded-e-sm justify-items-center w-min px-2.5 text-zinc-100 focus:outline-0 focus:from-zijpalm-600 focus:to-zijpalm-400 focus:inset-shadow-300 hover:from-zijpalm-600 hover:to-zijpalm-400 hover:inset-shadow-300">
                    <flux:icon.arrow-up-tray class="group-hover/file:scale-110 group-focus/file:scale-110 group-focus/file:brightness-110 duration-300 size-4.5 stroke-3"/>
                </button>
            </div>
        </div>
    @endif

    {{-- Merge attributes again, in case new classes are sent inline --}}
    @if($type == 'editor')
        <x-text-editor {{$attributes->merge($inputAttributes->all())->merge(['editordata' => old($id) ?? $value])}}/>
    @endif

    @if($type == 'radio')
        <div name="radio" class="flex flex-col items-start">
            @foreach ($options as $option => $optionName)
                @php
                    // Set the checked state based on the old input value
                    $checked = old($name) == $option;
                    // If the loop is the first iteration, set the checked state to true if the old input value is empty
                    if($loop->first) {
                        $checked = old($name) == $option || old($name) == '';
                    }
                @endphp
                <div>
                    <input {{$radioAttributes}}  id="{{$option}}" value="{{$option}}" @checked($checked)>
                    <label for="{{$option}}">{{$optionName}}</label>
                </div>
            @endforeach
        </div>
    @endif

    @if($type === 'phone')
        <div class="flex items-center w-full">
            <span class="text-md font-semibold text-zinc-600 select-none">06</span>
            <input {{$phoneAttributes}}>
        </div>
    @endif

    {{-- Optional, if no type is given, field is not readonly, and a slot is given --}}
    @if(!$readonly && $slot->isNotEmpty())
        {{$slot}}
    @endif
    </div>
</div>

<style>
</style>
