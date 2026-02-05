// Add to window for global access
window.toggleRequiredTooltip = toggleRequiredTooltip;
window.toggleInformationTooltip = toggleInformationTooltip;

// Function to toggle the red required asterisk and tooltip visibility
function toggleRequiredTooltip(input){

    // Handle editorjs-data input specifically
    if(input.id === 'editorjs-data'){
        input.id = input.getAttribute('name');
    }

    // Create a new MutationObserver to watch for changes in the 'required' attribute
    new MutationObserver(
        // Callback function to execute when the 'required' attribute changes
        function(mutations){
            document.getElementById(`${input.id}tooltip`).classList.toggle('hidden', !input.required);
        }
    ).observe(input, {attributes: true, attributeFilter: ['required']});
}

// Function to toggle the information tooltip visibility
function toggleInformationTooltip(input){

}