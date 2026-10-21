// Assign the function to the window object
window.displayUploadedFileName = displayUploadedFileName;

// Function takes an input (what provides the file) and target (what should change its contents)
function displayUploadedFileName(input){
    const target = document.getElementById(input.id + '-file-name');
    const file = input.files?.[0];
    if (target) {
        target.textContent = file?.name ?? 'Geen bestand gekozen';
        target.classList.toggle('font-semibold', !!file);
    }

    const preview = document.querySelector(`[data-image-preview="${input.id}"]`);
    if (!preview) {
        return;
    }
    if (preview.dataset.objectUrl) {
        URL.revokeObjectURL(preview.dataset.objectUrl);
        delete preview.dataset.objectUrl;
    }

    const image = preview.querySelector('img');
    const url = file && file.type.startsWith('image/') ? URL.createObjectURL(file) : preview.dataset.initialUrl;
    if (file && file.type.startsWith('image/')) {
        preview.dataset.objectUrl = url;
    }
    if (url) {
        image.src = url;
    } else {
        image.removeAttribute('src');
    }
    preview.hidden = !url;
}
