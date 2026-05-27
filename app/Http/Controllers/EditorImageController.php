<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Routing\Controller;

class EditorImageController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
        ]);

        $file = $request->file('image');
        $path = $file->storePubliclyAs('editor-images', (string) Str::uuid() . '.' . $file->getClientOriginalExtension(), ['disk' => 'public']);

        // Return the publicly accessible URL for the uploaded image.
        return response()->json([
            'url' => Storage::disk('public')->url($path),
        ], 201);
    }
}
