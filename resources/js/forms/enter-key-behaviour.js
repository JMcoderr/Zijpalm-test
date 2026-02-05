// Global variables to track input fields and current index
let inputFields = [];
let currentIndex = 0;

// Add listeners to all inputs on the page
function addInputListeners(){
    // Selects all inputs (that aren't hidden, and also textareas and buttons) and assigns them to inputFields array
    inputFields = Array.from(document.querySelectorAll('input:not([class="hidden"]), textarea, button'));

    // Add a listener to each input field, when it's focused, change the currentIndex to that field's index in inputFields array
    inputFields.forEach(
        function(input, index){
            input.addEventListener(
                'focus', 
                function(){
                    currentIndex = index;
                }
            );
        }
    );
}

// Function to handle Enter key behavior
function enterKeyBehaviour(event){
    if(event.key === 'Enter'){
        event.preventDefault();
        currentIndex = (currentIndex + 1) % inputFields.length;
        inputFields[currentIndex].focus();
    }
}

// Once the window loads, do these things
window.onload = function(){
    // Add listeners to all inputs on screen by default
    addInputListeners();

    // Add a listener to when a key is pressed
    document.addEventListener('keydown', enterKeyBehaviour);

    // Observe changes of everything in the body, including it's children and grandchildren, add inputs if they appear
    new MutationObserver(addInputListeners).observe(document.body, {childList: true, subtree: true});
};