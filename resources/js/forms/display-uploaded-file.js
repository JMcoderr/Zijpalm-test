window.displayUploadedFile = displayUploadedFile;

function displayUploadedFile(input, previewId) {
    const previewElement = document.getElementById(previewId);

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            previewElement.src = e.target.result + "#toolbar=0";
            previewElement.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        previewElement.src = '';
        previewElement.style.display = 'none';
    }

}