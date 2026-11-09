<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReplaceDocumentRequest;
use App\Http\Requests\StoreDocumentsRequest;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $documents = Document::with('uploader')->orderBy('original_name')->get();
        $folderPaths = collect();

        foreach ($documents as $document) {
            $path = '';

            foreach (array_filter(explode('/', (string) $document->folder_path)) as $segment) {
                $path = $path === '' ? $segment : $path . '/' . $segment;
                $folderPaths->push($path);
            }
        }

        $folderPaths = $folderPaths->unique()->values();
        $folderOptions = $folderPaths
            ->sort()
            ->map(fn ($path) => ['path' => $path, 'label' => str_replace('/', ' / ', $path)])
            ->values();
        $requestedFolder = $request->query('folder', '');
        abort_unless(is_string($requestedFolder), 404);
        $currentFolder = trim($requestedFolder, '/');
        abort_unless($currentFolder === '' || $folderPaths->contains($currentFolder), 404);

        $prefix = $currentFolder === '' ? '' : $currentFolder . '/';
        $folders = $folderPaths
            ->filter(fn ($path) => str_starts_with($path, $prefix) && $path !== $currentFolder)
            ->map(function ($path) use ($prefix, $documents) {
                $name = Str::before(substr($path, strlen($prefix)), '/');

                if ($name === '') {
                    return null;
                }

                $folderPath = $prefix . $name;
                $count = $documents->filter(fn ($document) => $document->folder_path === $folderPath || str_starts_with((string) $document->folder_path, $folderPath . '/'))->count();

                return ['name' => $name, 'path' => $folderPath, 'count' => $count];
            })
            ->filter()
            ->unique('path')
            ->sortBy('name')
            ->values();

        $folderDocuments = $documents->where('folder_path', $currentFolder)->values();
        $breadcrumbPath = '';
        $breadcrumbs = collect(explode('/', $currentFolder))
            ->filter()
            ->map(function ($segment) use (&$breadcrumbPath) {
                $breadcrumbPath = $breadcrumbPath === '' ? $segment : $breadcrumbPath . '/' . $segment;

                return ['name' => $segment, 'path' => $breadcrumbPath];
            })
            ->values();
        $searchNames = $folderDocuments
            ->map(fn ($document) => mb_strtolower($document->original_name . ' ' . $document->uploader?->name))
            ->concat($folders->pluck('name')->map(fn ($name) => mb_strtolower($name)))
            ->values();

        return view('admin.documents', [
            'breadcrumbs' => $breadcrumbs,
            'currentFolder' => $currentFolder,
            'folderDocuments' => $folderDocuments,
            'folderOptions' => $folderOptions,
            'folders' => $folders,
            'searchNames' => $searchNames,
            'totalFiles' => $documents->count(),
        ]);
    }

    public function store(StoreDocumentsRequest $request)
    {
        $paths = [];

        try {
            $relativePaths = $request->validated('relative_paths', []);
            $currentFolder = (string) $request->validated('current_folder', '');

            DB::transaction(function () use ($request, $relativePaths, $currentFolder, &$paths) {
                foreach ($request->validated('files') as $index => $file) {
                    $relativePath = str_replace('\\', '/', $relativePaths[$index] ?? $file->getClientOriginalName());
                    $segments = explode('/', $relativePath);
                    $originalName = array_pop($segments);
                    $folderPath = trim($currentFolder . '/' . implode('/', $segments), '/');
                    $path = $file->store('', 'admin_documents');
                    $paths[] = $path;
                    $extension = strtolower($file->getClientOriginalExtension());

                    Document::create([
                        'original_name' => $originalName,
                        'path' => $path,
                        'category' => in_array($extension, ['doc', 'docx'], true) ? 'word' : (in_array($extension, ['xls', 'xlsx'], true) ? 'excel' : 'pdf'),
                        'folder_path' => $folderPath,
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

        return redirect()->route('admin.documents.index', $currentFolder === '' ? [] : ['folder' => $currentFolder])
            ->with('success', count($paths) === 1 ? 'Bestand toegevoegd.' : count($paths) . ' bestanden toegevoegd.');
    }

    public function download(Document $document)
    {
        abort_unless(Storage::disk('admin_documents')->exists($document->path), 404);

        return Storage::disk('admin_documents')->download($document->path, $document->original_name, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function replace(ReplaceDocumentRequest $request, Document $document)
    {
        $file = $request->file('file');
        $originalName = basename(str_replace('\\', '/', $file->getClientOriginalName()));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $oldPath = $document->path;
        $newPath = $file->store('', 'admin_documents');

        if (!is_string($newPath)) {
            return back()->withInput()->withErrors(['file' => 'Het bestand kon niet worden opgeslagen. Probeer het opnieuw.']);
        }

        try {
            DB::transaction(function () use ($document, $request, $originalName, $extension, $newPath, $file) {
                $document->update([
                    'original_name' => $originalName,
                    'path' => $newPath,
                    'category' => in_array($extension, ['doc', 'docx'], true) ? 'word' : (in_array($extension, ['xls', 'xlsx'], true) ? 'excel' : 'pdf'),
                    'size' => $file->getSize(),
                    'user_id' => $request->user()->id,
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk('admin_documents')->delete($newPath);
            report($exception);

            return back()->withInput()->withErrors(['file' => 'Vervangen is niet gelukt. Probeer het opnieuw.']);
        }

        Storage::disk('admin_documents')->delete($oldPath);

        return redirect()->route('admin.documents.index', $document->folder_path === '' ? [] : ['folder' => $document->folder_path])
            ->with('success', 'Bestand vervangen.');
    }

    public function destroy(Request $request, Document $document)
    {
        Storage::disk('admin_documents')->delete($document->path);
        $document->delete();

        $currentFolder = $request->query('folder');

        return redirect()->route('admin.documents.index', is_string($currentFolder) && $currentFolder !== '' ? ['folder' => $currentFolder] : [])
            ->with('success', 'Bestand verwijderd.');
    }
}