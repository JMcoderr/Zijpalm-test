<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentsRequest;
use App\Models\Document;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DocumentController extends Controller
{
    public function index()
    {
        $documents = Document::with('uploader')->orderBy('original_name')->get()->groupBy('category');

        return view('admin.documents', compact('documents'));
    }

    public function store(StoreDocumentsRequest $request)
    {
        $paths = [];

        try {
            DB::transaction(function () use ($request, &$paths) {
                foreach ($request->validated('files') as $file) {
                    $path = $file->store('', 'admin_documents');
                    $paths[] = $path;

                    Document::create([
                        'original_name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'category' => in_array(strtolower($file->getClientOriginalExtension()), ['doc', 'docx'], true) ? 'word' : 'excel',
                        'size' => $file->getSize(),
                        'user_id' => $request->user()->id,
                    ]);
                }
            });
        } catch (Throwable $exception) {
            Storage::disk('admin_documents')->delete($paths);
            report($exception);

            return back()->withErrors(['files' => 'Uploaden is niet gelukt. Probeer het opnieuw.']);
        }

        return redirect()->route('admin.documents.index')->with('success', count($paths) === 1 ? 'Bestand toegevoegd.' : count($paths) . ' bestanden toegevoegd.');
    }

    public function download(Document $document)
    {
        abort_unless(Storage::disk('admin_documents')->exists($document->path), 404);

        return Storage::disk('admin_documents')->download($document->path, $document->original_name, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(Document $document)
    {
        Storage::disk('admin_documents')->delete($document->path);
        $document->delete();

        return redirect()->route('admin.documents.index')->with('success', 'Bestand verwijderd.');
    }
}