<?php
// This file is part of the app logic and has a short comment so it is easier to read.


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
        // These are the content types that are allowed here.
        // Right now the list is small, but this makes it easy to add more later.
        $validTypes = ['bestuurslid', 'test'];

        return view('content.create', compact('type'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreContentRequest $request)
    {
        // Check which file was uploaded and remember the matching file type.
        // Only one upload type is expected here.
        if ($request->hasFile('image')) {
            $fileType = FileType::Image;
            $file = $request->file('image');
        } elseif ($request->hasFile('pdf')) {
            $fileType = FileType::Pdf;
            $file = $request->file('pdf');
        }

        // Save the file in the right place depending on the file type.
        // Images go through the helper because they need extra handling.
        if ($fileType == FileType::Image) {
            $filePath = uploadImage($file, 'images/bestuur/');
        } elseif ($fileType == FileType::Pdf) {
            $filePath = $file->store('content/pdf', 'public');
        }

        // Save the new content record in the database.
        // The name gets a number so multiple items of the same type stay unique.
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
        // Keep the previous page so the user can go back after editing.
        session(['return_to' => url()->previous()]);

        return view('content.update', compact('content'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateContentRequest $request, Content $content)
    {
        // Keep the old name so we can clear the cache later if needed.
        $name = $request->name ?? $content->name;
        $old_name = $content->name;
        // dd(session('return_to'));
        // Check if a new file was uploaded and remember the file type.
        if ($request->hasFile('image')) {
            $fileType = FileType::Image;
            $file = $request->file('image');
        } elseif ($request->hasFile('pdf')) {
            $fileType = FileType::Pdf;
            $file = $request->file('pdf');
        }

        // Don't allow switching from image to PDF or the other way around.
        if (isset($fileType) && $fileType != $content->fileType) {
            return redirect()->back()->with('error', 'Je kan het bestand niet veranderen van type. Upload een ' . $content->fileType->value . ' bestand.');
        }

        // If there is a new file, replace the old one first.
        if(isset($fileType)) {
            if ($content->filePath && Storage::disk('public')->exists($content->filePath)) {
                Storage::disk('public')->delete($content->filePath);
            }

            // Save the new file again in the right format.
            if ($fileType == FileType::Image) {
                $filePath = uploadImage($file, 'images/bestuur/');
            } elseif ($fileType == FileType::Pdf) {
                $filePath = $file->store('content/pdf', 'public');
            }
        }

        // If this content has a related report image, update that too.
        if ($content->report && $request->hasFile('report-image')) {
            if ($content->report->imagePath && Storage::disk('public')->exists($content->report->imagePath)) {
                Storage::disk('public')->delete($content->report->imagePath);
            }

            $content->report->update([
                'imagePath' => uploadImage($request->file('report-image'), 'images/reports/'),
            ]);
        }

        // Keep the description required when the content already had text.
        if ($content->text && !$request->description) {
            return redirect()->back()->with('error', 'De beschrijving is verplicht.');
        } else if ($request->description) {
            // Keep editor JSON payload intact so paragraph/newline blocks are preserved.
            if ($content->type === 'bestuurslid' || $content->type === 'file') {
                $text = html_entity_decode($request->description, ENT_QUOTES, 'UTF-8');
            } else {
                $text = $request->description;
            }
        } else {
            $text = null;
        }

        // If this is the Nieuwe Activiteit email and an extra field was provided,
        // merge the extra text into the EditorJS JSON payload as an additional paragraph block.
        if (($content->name === 'email-nieuwe-activiteit' || ($name ?? '') === 'email-nieuwe-activiteit') && $request->filled('extra')) {
            $extra = $request->input('extra');

            // Try to decode existing EditorJS payload
            $payload = null;
            if ($text) {
                $decoded = json_decode($text, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $payload = $decoded;
                }
            }

            $block = ['type' => 'paragraph', 'data' => ['text' => $extra]];

            if ($payload && isset($payload['blocks']) && is_array($payload['blocks'])) {
                $payload['blocks'][] = $block;
            } else {
                // Create a new payload. If $text contained plain HTML/plaintext, preserve it as first paragraph.
                $first = null;
                if ($text) {
                    $first = ['type' => 'paragraph', 'data' => ['text' => $text]];
                }

                $payload = [
                    'time' => now()->timestamp * 1000,
                    'blocks' => array_filter([$first, $block]),
                    'version' => '2.31.0',
                ];
            }

            $text = json_encode($payload, JSON_UNESCAPED_UNICODE);
        }


        // If no new file was uploaded, keep the old file path.
        $filePath = $filePath ?? $content->filePath;

        // Update the content record with the new values.
        $content->update([
            'filePath' => $filePath,
            'title' => $request->title,
            'text' => $text,
            'name' => $name ?? $content->name,
        ]);

        // Clear the cache because this content may be reused on other pages.
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
        // Only board member content can be removed here.
        $allowed = false;
        if ($content->type === 'bestuurslid')
            $allowed = true;

        // If the type is not allowed, stop here.
        if (!$allowed)
            return back()->with('error', "$content->type mag niet verwijdert worden.");

        // Delete the record from the database.
        $content->delete();

        return back()->with('success', 'Succesvol verwijderd van ' . $content->name);
    }
}
