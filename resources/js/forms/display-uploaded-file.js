// This JavaScript file is part of the frontend logic and has a short comment so it is easier to follow.
window.displayUploadedFile = displayUploadedFile;

function displayUploadedFile(input, previewId) {
    // Find the preview element where the file should be shown.
    const previewElement = document.getElementById(previewId);

    if (input.files && input.files[0]) {
        // Read the selected file and show it in the preview.
        const reader = new FileReader();
        reader.onload = function (e) {
            previewElement.src = e.target.result + "#toolbar=0";
            previewElement.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        // If nothing is selected, hide the preview again.
        previewElement.src = '';
        previewElement.style.display = 'none';
    }

}