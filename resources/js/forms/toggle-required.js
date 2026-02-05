// Add to the window for global access
window.toggleRequiredOnChecked = toggleRequiredOnChecked;

window.toggleRequired = toggleRequired;

// resources/js/form/toggle-required.js

window.toggleRecurringOnChecked = toggleRecurringOnChecked;

function toggleRecurringOnChecked(checkbox, inputs) {
    // Check if given checkbox is valid
    if(!(checkbox instanceof HTMLInputElement) && checkbox.type !== 'checkbox'){
        throw new Error("Given element is not a checkbox");
    }

    // Check if all entries are valid inputs
    if(!inputs.entries(input => input instanceof HTMLInputElement)){
        throw new Error("Given elements are not inputs");
    }

    // Listen for checkbox changes and update inputs required state
    checkbox.addEventListener(
        // Listened Event
        'change',
        // Performed Action
        inputs.forEach(
            function(input){
                // If any input but the given checkbox (checkbox here is the variable, not the HTMLInputElement)
                if(input !== checkbox){
                    // Expects custom component <x-input-field>, as they always have a tooltip with the correct format for id
                    if(document.getElementById(`${input.id}tooltip`) != null){
                        // Change required status based on invert
                        input.required = !checkbox.checked;
                        // Forcibly hide the tooltip, which includes the asterisk
                        document.getElementById(`${input.id}tooltip`).classList.toggle('hidden');
                        // Forcibly set values to null for time inputs and hide Label and Input
                        input.value = null;
                        input.parentElement.parentElement.classList.toggle('hidden');
                    }
                }
            }
        )
    );
}

// Function to toggle the 'required' attribute on input fields based on a checkbox state
function toggleRequiredOnChecked(checkbox, inputs, invert = false){
    // Check if given checkbox is valid
    if(!(checkbox instanceof HTMLInputElement) && checkbox.type !== 'checkbox'){
        throw new Error("Given element is not a checkbox");
    }

    // Check if all entries are valid inputs
    if(!inputs.entries(input => input instanceof HTMLInputElement)){
        throw new Error("Given elements are not inputs");
    }

    // Listen for checkbox changes and update inputs required state
    checkbox.addEventListener(
        // Listened Event
        'change',
        // Performed Action
        inputs.forEach(
            function(input){
                // If any input but the given checkbox (checkbox here is the variable, not the HTMLInputElement)
                if(input != checkbox){
                    // Expects custom component <x-input-field>, as they always have a tooltip with the correct format for id
                    if(document.getElementById(`${input.id}tooltip`) != null){
                        // Change required status based on invert
                        input.required = invert ? !checkbox.checked : checkbox.checked;
                        // Forcibly hide the tooltip, which includes the asterisk
                        document.getElementById(`${input.id}tooltip`).classList.toggle('hidden');
                    }
                }
            }
        )
    );
}

/**
 * Toggles the 'required' attribute on multiple input fields based on a trigger input's state.
 * @param {HTMLInputElement|HTMLSelectElement} trigger - The input element that triggers the function
 * @param {Array<HTMLInputElement>} targets - An array of input elements to toggle the 'required' attribute on
 * @param {any} condition - The condition to check when to toggle the required state
 * @param {Array<function>} [actions = null] - Optional additional actions to perform when toggling the required state
 */
function toggleRequired(trigger, targets, condition, actions = null){

    // Check if the trigger and targets are valid HTMLInputElements
    function validateInputs(trigger, targets){

        // If no trigger is given, throw an error
        if(!trigger){
            throw new Error('No trigger given');
        }

        // Validate the trigger
        if(!(trigger instanceof HTMLInputElement) && !(trigger instanceof HTMLSelectElement)){
            throw new Error('Trigger is not a valid HTMLInputElement or HTMLSelectElement');
        }

        // If targets is not an array, throw an error
        if(!Array.isArray(targets)){
            throw new Error('Targets is not an array');
        }

        // If targets is an empty array, throw an error
        if(targets.length == 0){
            throw new Error('Targets array is empty');
        }

        // While this makes sense, we have Editor components that are not HTMLInputElements, so we can't validate them
        // Validate the targets
        // if(!targets.every(target => target instanceof HTMLInputElement)){
        //     throw new Error('One or more targets are not valid HTMLInputElements');
        // }

        // Instead of an error, remove trigger from the targets if it's included
        if(targets.includes(trigger)){
            targets = targets.filter(target => target !== trigger);
        }

        // Return the validated trigger and targets
        return {trigger, targets};
    }

    // Validate additional actions
    function validateActions(actions){
        // If actions is not an array, throw an error
        if(!Array.isArray(actions)){
            throw new Error('Actions is not an array');
        }

        // If actions are not functions, throw an error
        if(!actions.every(action => typeof action === 'function')){
            throw new Error('One or more actions are not functions');
        }

        // Return the validated actions (not needed, but for consistency)
        return actions;
    }

    // Return true/false, based on trigger type and condition
    function evaluateCondition(trigger, condition){
        let pass = false;

        // If the trigger is a checkbox or radio button, check if it's checked
        if(trigger.type === 'checkbox' || trigger.type === 'radio'){
            pass = (trigger.checked === condition);
        }

        // If the trigger is a select element, check if value matches condition, skip over default option
        if(trigger instanceof HTMLSelectElement){
            if(trigger.value !== '0' && trigger.options[trigger.selectedIndex]?.text.trim() !== '-'){
                pass = (trigger.value === condition);
            }
        }

        // Return the result of the condition evaluation
        return pass;
    }

    // Check if target has a tooltip element
    function tooltip(target){
        return document.getElementById(`${target.id}tooltip`);
    }

    // Set the listener
    function setListener(trigger, targets, condition, actions){
        // Add an event listener to the trigger
        trigger.addEventListener(
            // Listened Event
            'change',
            // Performed Action
            function(){
                // Toggle the 'required' attribute on each target based on the trigger's state
                targets.forEach(
                    function(target){
                        // Toggle required
                        target.required = evaluateCondition(trigger, condition);

                        // Check if the target has a tooltip element and toggle visibility
                        tooltip(target)?.classList.toggle('hidden');
                    }
                );

                // If additional actions are provided, perform them
                if(actions){
                    actions.forEach(action => action());
                }
            }
        );
    }

    // Run the functions in order
    // #1 - Validate inputs and update the local variables
    ({trigger, targets} = validateInputs(trigger, targets));

    // #2 - Validate actions, if given
    if(actions) actions = validateActions(actions);

    // #3 - Set the listener on the trigger
    setListener(trigger, targets, condition, actions);
}
