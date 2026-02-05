<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

/**
 * Uploads an image file to the specified directory, resizes it, and converts it to WebP format.
 *
 * @param UploadedFile $file The uploaded file instance.
 * @param string $directory The directory where the file should be stored.
 * @return string The relative file path of the stored image.
 */
function uploadImage(UploadedFile $file, string $directory = 'images/'):string {
    // Hash the file name
    $fileName = $file->hashName();

    // Remove the file extension
    $fileName = pathinfo($fileName, PATHINFO_FILENAME);

    // Resize the file and convert it to webp
    $image = Image::read($file);
    $resizedWebp = $image->scaleDown(1000, null)->toWebp(100);

    // Construct the file path
    // $directory = 'images/bestuur/';
    $filePath = $directory . $fileName . '.webp';

    // Check if the directory exists, if not create it
    if (!Storage::disk('public')->directoryExists($directory)) {
        Storage::disk('public')->makeDirectory($directory);
    }

    // Get the absolute path
    $absolutePath = Storage::disk('public')->path($filePath);

    // Store the file
    $resizedWebp->save($absolutePath);

    return $filePath;
}