<?php

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('admin_documents');
    Storage::fake('public');
    $this->admin = User::factory()->admin()->create();
});

it('stores admin documents privately and downloads them with the original name', function () {
    $file = UploadedFile::fake()->create('Notulen.docx', 32, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

    $this->actingAs($this->admin)->post(route('admin.documents.store'), ['files' => [$file]])
        ->assertSessionHasNoErrors()->assertRedirect(route('admin.documents.index'));

    $document = Document::sole();
    expect($document->category)->toBe('word')
        ->and($document->original_name)->toBe('Notulen.docx')
        ->and($document->folder_path)->toBe('')
        ->and($document->user_id)->toBe($this->admin->id)
        ->and($document->path)->not->toBe('Notulen.docx');
    Storage::disk('admin_documents')->assertExists($document->path);
    Storage::disk('public')->assertMissing($document->path);

    $this->get(route('admin.documents.download', $document))->assertOk()->assertDownload('Notulen.docx')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

it('shows a folder-based document explorer and an admin navigation link next to charity', function () {
    $this->actingAs($this->admin)->get(route('admin.documents.index'))->assertOk()
        ->assertSeeInOrder(['Lief &amp; Leed', 'Bestanden'], false)
        ->assertSee('data-document-explorer', false)
        ->assertSee('Alle bestanden')
        ->assertSee('name="files[]"', false)
        ->assertSee('webkitdirectory', false)
        ->assertSee('.pdf')
        ->assertSee('Bestanden of map uploaden')
        ->assertSee('Uploaden');
});

it('stores PDF uploads in their folder hierarchy and browses nested folders', function () {
    $pdfPath = tempnam(sys_get_temp_dir(), 'document-test');
    file_put_contents($pdfPath, "%PDF-1.4\n1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n2 0 obj << /Type /Pages /Kids [] /Count 0 >> endobj\ntrailer << /Root 1 0 R >>\n%%EOF");

    try {
        $file = new UploadedFile($pdfPath, 'Offerte woning.pdf', null, null, true);

        $this->actingAs($this->admin)->post(route('admin.documents.store'), [
            'files' => [$file],
            'relative_paths' => ['Offertes/2026/Offerte woning.pdf'],
            'current_folder' => '',
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.documents.index'));

        $document = Document::sole();
        expect($document->category)->toBe('pdf')
            ->and($document->original_name)->toBe('Offerte woning.pdf')
            ->and($document->folder_path)->toBe('Offertes/2026');

        $this->get(route('admin.documents.index'))->assertOk()->assertSee('Offertes')->assertDontSee('Offerte woning.pdf');
        $this->get(route('admin.documents.index', ['folder' => 'Offertes']))->assertOk()->assertSee('2026')->assertDontSee('Offerte woning.pdf');
        $this->get(route('admin.documents.index', ['folder' => 'Offertes/2026']))->assertOk()->assertSee('Offerte woning.pdf');
        $this->get(route('admin.documents.download', $document))->assertOk()->assertDownload('Offerte woning.pdf');

        $this->post(route('admin.documents.store'), [
            'files' => [UploadedFile::fake()->create('Aanvulling.pdf', 16, 'application/pdf')],
            'relative_paths' => ['Aanvulling.pdf'],
            'current_folder' => 'Offertes/2026',
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.documents.index', ['folder' => 'Offertes/2026']));

        $addition = Document::where('original_name', 'Aanvulling.pdf')->sole();
        expect($addition->folder_path)->toBe('Offertes/2026');
        $this->delete(route('admin.documents.destroy', ['document' => $addition, 'folder' => 'Offertes/2026']))
            ->assertRedirect(route('admin.documents.index', ['folder' => 'Offertes/2026']));
    } finally {
        unlink($pdfPath);
    }
});

it('replaces an edited document in place and keeps its folder', function () {
    Storage::disk('admin_documents')->put('old-offer.pdf', 'old version');
    $document = Document::create([
        'original_name' => 'Offerte.pdf', 'path' => 'old-offer.pdf', 'category' => 'pdf',
        'folder_path' => 'Offertes/2026', 'size' => 12, 'user_id' => $this->admin->id,
    ]);

    $this->actingAs($this->admin)->post(route('admin.documents.replace', $document), [
        'file' => UploadedFile::fake()->create('Offerte aangepast.pdf', 24, 'application/pdf'),
    ])->assertSessionHasNoErrors()->assertRedirect(route('admin.documents.index', ['folder' => 'Offertes/2026']));

    $document->refresh();
    expect($document->original_name)->toBe('Offerte aangepast.pdf')
        ->and($document->folder_path)->toBe('Offertes/2026')
        ->and($document->category)->toBe('pdf')
        ->and($document->path)->not->toBe('old-offer.pdf');
    Storage::disk('admin_documents')->assertMissing('old-offer.pdf');
    Storage::disk('admin_documents')->assertExists($document->path);
});

it('keeps the existing document when a replacement file is invalid', function () {
    Storage::disk('admin_documents')->put('old-offer.pdf', 'old version');
    $document = Document::create([
        'original_name' => 'Offerte.pdf', 'path' => 'old-offer.pdf', 'category' => 'pdf',
        'folder_path' => 'Offertes/2026', 'size' => 12, 'user_id' => $this->admin->id,
    ]);
    $folderUrl = route('admin.documents.index', ['folder' => $document->folder_path]);

    $this->actingAs($this->admin)->from($folderUrl)->post(route('admin.documents.replace', $document), [
        'replace_document_id' => $document->id,
        'file' => UploadedFile::fake()->create('Niet toegestaan.txt', 12, 'text/plain'),
    ])->assertRedirect($folderUrl)->assertSessionHasErrors('file');

    $this->get($folderUrl)->assertOk()
        ->assertSee('Alleen PDF-, Word- en Excel-bestanden zijn toegestaan.')
        ->assertSee('replaceDocumentModal: true', false);

    expect($document->fresh()->path)->toBe('old-offer.pdf')
        ->and($document->fresh()->original_name)->toBe('Offerte.pdf');
    Storage::disk('admin_documents')->assertExists('old-offer.pdf');
    expect(Storage::disk('admin_documents')->allFiles())->toBe(['old-offer.pdf']);
});

it('rejects unsafe folder paths and mismatched folder upload metadata', function (string $relativePath) {
    $this->actingAs($this->admin)->from(route('admin.documents.index'))->post(route('admin.documents.store'), [
        'files' => [UploadedFile::fake()->create('Offerte.pdf', 16, 'application/pdf')],
        'relative_paths' => [$relativePath],
    ])->assertRedirect(route('admin.documents.index'))->assertSessionHasErrors();

    expect(Document::count())->toBe(0)
        ->and(Storage::disk('admin_documents')->allFiles())->toBe([]);
})->with([
    '../Offerte.pdf',
    'Offertes/../../Offerte.pdf',
    'C:/Offerte.pdf',
    'Offertes/Andere naam.pdf',
]);

it('rejects an unsafe destination folder and unknown folder URLs', function () {
    $this->actingAs($this->admin)->from(route('admin.documents.index'))->post(route('admin.documents.store'), [
        'files' => [UploadedFile::fake()->create('Offerte.pdf', 16, 'application/pdf')],
        'current_folder' => '../buiten-de-bibliotheek',
    ])->assertRedirect(route('admin.documents.index'))->assertSessionHasErrors('current_folder');

    $this->get(route('admin.documents.index', ['folder' => 'Onbekende-map']))->assertNotFound();
    $this->get(route('admin.documents.index') . '?folder%5B%5D=Onbekend')->assertNotFound();
    expect(Document::count())->toBe(0)
        ->and(Storage::disk('admin_documents')->allFiles())->toBe([]);
});

it('denies members and guests all document routes and hides the navigation link', function () {
    Storage::disk('admin_documents')->put('private.docx', 'document');
    $document = Document::create([
        'original_name' => 'Privaat.docx', 'path' => 'private.docx', 'category' => 'word',
        'size' => 8, 'user_id' => $this->admin->id,
    ]);

    foreach ([null, User::factory()->create()] as $user) {
        if ($user) {
            $this->actingAs($user);
        }
        $redirect = route($user ? 'home' : 'login');
        $this->get(route('admin.documents.index'))->assertRedirect($redirect);
        $this->get(route('admin.documents.download', $document))->assertRedirect($redirect);
        $this->post(route('admin.documents.store'), [])->assertRedirect($redirect);
        $this->post(route('admin.documents.replace', $document), [])->assertRedirect($redirect);
        $this->delete(route('admin.documents.destroy', $document))->assertRedirect($redirect);
        $this->get(route('activity.index'))->assertOk()->assertDontSee(route('admin.documents.index'), false);
    }

    expect(Document::count())->toBe(1);
    Storage::disk('admin_documents')->assertExists($document->path);
});

it('supports all four document extensions and classifies them into the correct lists', function (string $extension, string $mime, string $category) {
    $this->actingAs($this->admin)->post(route('admin.documents.store'), [
        'files' => [UploadedFile::fake()->create('Voorbeeld.' . $extension, 16, $mime)],
    ])->assertSessionHasNoErrors()->assertRedirect(route('admin.documents.index'));

    expect(Document::sole()->category)->toBe($category);
})->with([
    ['doc', 'application/msword', 'word'],
    ['docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'word'],
    ['xls', 'application/vnd.ms-excel', 'excel'],
    ['xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'excel'],
]);

it('accepts actual Excel and Word contents in a single batch', function () {
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $spreadsheet->getActiveSheet()->setCellValue('A1', 'Begroting');
    $excelPath = tempnam(sys_get_temp_dir(), 'document-test');
    $wordPath = tempnam(sys_get_temp_dir(), 'document-test');

    try {
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($excelPath);
        $zip = new ZipArchive();
        $zip->open($wordPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
        $zip->addFromString('word/document.xml', '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>Notulen</w:t></w:r></w:p></w:body></w:document>');
        $zip->close();

        $this->actingAs($this->admin)->post(route('admin.documents.store'), [
            'files' => [
                new UploadedFile($excelPath, 'Begroting.xlsx', null, null, true),
                new UploadedFile($wordPath, 'Notulen.docx', null, null, true),
            ],
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.documents.index'));

        expect(Document::orderBy('category')->pluck('category')->all())->toBe(['excel', 'word']);
        $this->get(route('admin.documents.index'))->assertOk()->assertSee('Begroting.xlsx')->assertSee('Notulen.docx');
    } finally {
        $spreadsheet->disconnectWorksheets();
        unlink($excelPath);
        unlink($wordPath);
    }
});

it('rejects invalid files without keeping any part of the batch', function (string $scenario) {
    $invalidFile = match ($scenario) {
        'disguised' => new UploadedFile(__FILE__, 'Bestand.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true),
        'disguised-pdf' => new UploadedFile(__FILE__, 'Bestand.pdf', 'application/pdf', null, true),
        'oversized' => UploadedFile::fake()->create('Te groot.docx', 20481, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        'extension' => UploadedFile::fake()->create('Bestand.exe', 16, 'application/msword'),
    };

    $this->actingAs($this->admin)->from(route('admin.documents.index'))->post(route('admin.documents.store'), [
        'files' => [UploadedFile::fake()->create('Geldig.doc', 16, 'application/msword'), $invalidFile],
    ])->assertRedirect(route('admin.documents.index'))->assertSessionHasErrors('files.1');

    expect(Document::count())->toBe(0)
        ->and(Storage::disk('admin_documents')->allFiles())->toBe([]);
})->with(['disguised', 'disguised-pdf', 'oversized', 'extension']);

it('requires a file and rejects batches larger than five hundred', function () {
    $this->actingAs($this->admin)->post(route('admin.documents.store'), [])->assertSessionHasErrors('files');
    $files = array_map(fn ($index) => UploadedFile::fake()->create('Bestand-' . $index . '.doc', 1, 'application/msword'), range(1, 501));
    $this->post(route('admin.documents.store'), ['files' => $files])->assertSessionHasErrors('files');

    expect(Document::count())->toBe(0);
});

it('keeps duplicate filenames as independent files and removes only the chosen document', function () {
    $this->actingAs($this->admin)->post(route('admin.documents.store'), [
        'files' => [
            UploadedFile::fake()->create('Notulen.doc', 16, 'application/msword'),
            UploadedFile::fake()->create('Notulen.doc', 16, 'application/msword'),
        ],
    ])->assertSessionHasNoErrors();

    $documents = Document::orderBy('id')->get();
    expect($documents)->toHaveCount(2)
        ->and($documents[0]->path)->not->toBe($documents[1]->path);

    $this->delete(route('admin.documents.destroy', $documents[0]))->assertRedirect(route('admin.documents.index'));
    expect(Document::count())->toBe(1);
    Storage::disk('admin_documents')->assertMissing($documents[0]->path);
    Storage::disk('admin_documents')->assertExists($documents[1]->path);
    $this->get(route('admin.documents.download', $documents[0]->id))->assertNotFound();
});

it('returns not found when a stored document file is missing', function () {
    $document = Document::create([
        'original_name' => 'Ontbreekt.doc', 'path' => 'missing.doc', 'category' => 'word',
        'size' => 8, 'user_id' => $this->admin->id,
    ]);

    $this->actingAs($this->admin)->get(route('admin.documents.download', $document))->assertNotFound();
});

it('cleans up files and rolls back database rows when a batch cannot be saved', function () {
    Document::creating(function (Document $document) {
        if ($document->original_name === 'Failure.doc') {
            throw new RuntimeException('Simulated document persistence failure');
        }
    });

    try {
        $this->actingAs($this->admin)->from(route('admin.documents.index'))->post(route('admin.documents.store'), [
            'files' => [
                UploadedFile::fake()->create('Good.doc', 16, 'application/msword'),
                UploadedFile::fake()->create('Failure.doc', 16, 'application/msword'),
            ],
        ])->assertRedirect(route('admin.documents.index'))->assertSessionHasErrors('files');

        expect(Document::count())->toBe(0)
            ->and(Storage::disk('admin_documents')->allFiles())->toBe([]);
    } finally {
        Document::flushEventListeners();
    }
});

it('uses a private disk without public or signed file serving', function () {
    expect(config('filesystems.disks.admin_documents.serve'))->toBeFalse()
        ->and(config('filesystems.disks.admin_documents.visibility'))->toBe('private')
        ->and(config('filesystems.disks.admin_documents.root'))->toBe(storage_path('app/private/admin-documents'));
});