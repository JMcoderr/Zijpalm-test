// Assign the function to the window object
window.displayUploadedFileName = displayUploadedFileName;

// Function takes an input (what provides the file) and target (what should change its contents)
function displayUploadedFileName(input){
    const target = document.getElementById(input.id + '-file-name');

    // Replace old text colour, and make the font a little more obvious
    target.classList.replace('text-zinc-500', 'text-zijpalm-300');
    target.classList.add('font-semibold')

    // Target the target, set its inner HTML to the input file's name (files is always an array)
    target.innerHTML = input.files[0].name;
}
