<?php

namespace App\Http\Controllers;

use App\FileType;
use App\Http\Requests\StoreContentRequest;
use App\Http\Requests\UpdateContentRequest;
use App\Models\Content;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ContentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(string $type)
    {
        // Check if the type is valid
        $validTypes = ['bestuurslid', 'test'];

        return view('content.create', compact('type'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreContentRequest $request)
    {
        // Get the file type and the file
        if ($request->hasFile('image')) {
            $fileType = FileType::Image;
            $file = $request->file('image');
        } elseif ($request->hasFile('pdf')) {
            $fileType = FileType::Pdf;
            $file = $request->file('pdf');
        }

        // Save the file to the public directory
        if ($fileType == FileType::Image) {
            // Use the uploadImage function to handle the image upload
            $filePath = uploadImage($file, 'images/bestuur/');
        } elseif ($fileType == FileType::Pdf) {
            $filePath = $file->store('content/pdf', 'public');
        }

        Content::create([
            'type' => $request->type,
            'name' => $request->type . '-' . Content::where('type', $request->type)->count(),
            'filePath' => $filePath,
            'fileType' => $fileType,
            'title' => $request->title,
            'text' => $request->description,
        ]);

        return redirect()->back()->with('success', ucfirst($request->type) . ' is toegevoegd');
    }

    /**
     * Display the specified resource.
     */
    public function show(Content $content)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Content $content)
    {
        session(['return_to' => url()->previous()]);

        return view('content.update', compact('content'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateContentRequest $request, Content $content)
    {
        $name = $request->name ?? $content->name;
        $old_name = $content->name;
        // dd(session('return_to'));
        // Get the file type and the file
        if ($request->hasFile('image')) {
            $fileType = FileType::Image;
            $file = $request->file('image');
        } elseif ($request->hasFile('pdf')) {
            $fileType = FileType::Pdf;
            $file = $request->file('pdf');
        }

        // Check if the new file type is different from the current file type
        if (isset($fileType) && $fileType != $content->fileType) {
            // Return an error message
            return redirect()->back()->with('error', 'Je kan het bestand niet veranderen van type. Upload een ' . $content->fileType->value . ' bestand.');
        }

        // Check if a new file was uploaded
        if(isset($fileType)) {
            // Delete the old file if it exists
            if ($content->filePath && Storage::disk('public')->exists($content->filePath)) {
                Storage::disk('public')->delete($content->filePath);
            }

            // Save the new file to the public directory
            if ($fileType == FileType::Image) {
                $filePath = uploadImage($file, 'images/bestuur/');
                // Use the uploadImage function to handle the image upload
            } elseif ($fileType == FileType::Pdf) {
                $filePath = $file->store('content/pdf', 'public');
            }
        }

        // Check the description is not empty if content's text is not empty
        if ($content->text && !$request->description) {
            return redirect()->back()->with('error', 'De beschrijving is verplicht.');
        } else if ($request->description) {
            $text = html_entity_decode($request->description, ENT_QUOTES, 'UTF-8');
        } else {
            $text = null;
        }


        $filePath = $filePath ?? $content->filePath;

        $content->update([
            'filePath' => $filePath,
            'title' => $request->title,
            'text' => $text,
            'name' => $name ?? $content->name,
        ]);

        // Sometimes content is cahced, so we clear it on update
        // This is to ensure that the updated content is displayed correctly
        Cache::forget($old_name);

        // return redirect()->back()->with('success', ucfirst($content->name) . ' is aangepast');
        return redirect(session('return_to', route('content.edit', $content)))
            ->with('success', kebab_to_display($content->name) . ' is aangepast');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Content $content)
    {
        //
        $allowed = false;
        if ($content->type === 'bestuurslid')
            $allowed = true;

        if (!$allowed)
            return back()->with('error', "$content->type mag niet verwijdert worden.");

        $content->delete();

        return back()->with('success', 'Succesvol verwijderd van ' . $content->name);
    }
}
