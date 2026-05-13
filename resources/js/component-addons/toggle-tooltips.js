// Add to window for global access
window.toggleRequiredTooltip = toggleRequiredTooltip;
window.toggleInformationTooltip = toggleInformationTooltip;

// Function to toggle the red required asterisk and tooltip visibility
function toggleRequiredTooltip(input){

    // Handle editorjs-data input specifically
    if(input.id === 'editorjs-data'){
        // This editor input uses the name as a better tooltip target.
        input.id = input.getAttribute('name');
    }

    // Create a new MutationObserver to watch for changes in the 'required' attribute
    new MutationObserver(
        // Callback function to execute when the 'required' attribute changes
        function(mutations){
            // Show or hide the tooltip depending on whether the field is required.
            document.getElementById(`${input.id}tooltip`).classList.toggle('hidden', !input.required);
        }
    ).observe(input, {attributes: true, attributeFilter: ['required']});
}

// Function to toggle the information tooltip visibility
function toggleInformationTooltip(input){
    // This helper is still empty for now, but it is kept here so the API stays the same.
}