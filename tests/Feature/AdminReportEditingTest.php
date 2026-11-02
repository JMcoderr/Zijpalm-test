<?php

use App\FileType;
use App\Models\Content;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->admin = User::factory()->admin()->create();
    $this->content = Content::create([
        'type' => 'report',
        'name' => 'Vis a Vis Carrousel',
        'title' => 'Vis a Vis Carrousel',
        'fileType' => FileType::Pdf,
        'filePath' => 'content/pdf/carrousel.pdf',
    ]);
    Storage::disk('public')->put($this->content->filePath, 'existing pdf');
    $this->report = Report::create(['content_id' => $this->content->id, 'year' => 2026]);
});

it('shows visible report type choices and the current year in the existing edit form', function (?int $year, string $selectedType) {
    $this->report->update(['year' => $year]);

    $response = $this->actingAs($this->admin)
        ->get(route('content.edit', $this->content))
        ->assertOk()
        ->assertSee('Normaal verslag')
        ->assertSee('Jaarverslag')
        ->assertSee('name="report-type"', false)
        ->assertSee('name="report-year"', false)
        ->assertSee('value="' . ($year ?? now()->year) . '"', false);

    $document = new DOMDocument();
    $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);

    expect($document->getElementById('report-type-wrapper')->getElementsByTagName('legend')->item(0)->getAttribute('class'))
        ->toContain('text-start');

    $choices = $document->getElementById('report-type')->getElementsByTagName('input');
    expect($choices)->toHaveCount(2)
        ->and($document->getElementById('report-type')->getElementsByTagName('select'))->toHaveCount(0);

    foreach (['activity' => 'Normaal verslag', 'year' => 'Jaarverslag'] as $value => $label) {
        $choice = $document->getElementById('report-type-' . $value);
        expect($choice->getAttribute('type'))->toBe('radio')
            ->and($choice->getAttribute('name'))->toBe('report-type')
            ->and($choice->getAttribute('value'))->toBe($value)
            ->and($choice->getAttribute('x-model'))->toBe('reportType')
            ->and($choice->hasAttribute('checked'))->toBe($value === $selectedType)
            ->and(trim($choice->parentNode->textContent))->toBe($label);
    }

    expect($document->getElementById('report-year')->getAttribute('class'))
        ->toBe($document->getElementById('title')->getAttribute('class'));

    foreach (['report-type', 'report-year'] as $field) {
        expect($document->getElementById($field . '-wrapper')->getAttribute('class'))
            ->toBe($document->getElementById('title-wrapper')->getAttribute('class'));
    }
})->with([
    'normal report' => [null, 'activity'],
    'annual report' => [2026, 'year'],
]);

it('moves an annual report to normal reports without replacing its pdf', function () {
    $this->actingAs($this->admin)
        ->put(route('content.update', $this->content), [
            'title' => $this->content->title,
            'report-type' => 'activity',
            'report-year' => 2026,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($this->report->fresh()->year)->toBeNull()
        ->and($this->content->fresh()->filePath)->toBe('content/pdf/carrousel.pdf');
    Storage::disk('public')->assertExists('content/pdf/carrousel.pdf');

    $this->get(route('report.index'))
        ->assertOk()
        ->assertViewHas('activities', fn ($reports) => $reports->contains('id', $this->report->id))
        ->assertViewHas('years', fn ($reports) => !$reports->contains('id', $this->report->id));
});

it('moves a normal report to annual reports with the selected year', function () {
    $this->report->update(['year' => null]);

    $this->actingAs($this->admin)
        ->put(route('content.update', $this->content), [
            'title' => $this->content->title,
            'report-type' => 'year',
            'report-year' => 2025,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect((int) $this->report->fresh()->year)->toBe(2025);
    Storage::disk('public')->assertExists('content/pdf/carrousel.pdf');

    $this->get(route('report.index'))
        ->assertOk()
        ->assertViewHas('years', fn ($reports) => $reports->contains('id', $this->report->id))
        ->assertViewHas('activities', fn ($reports) => !$reports->contains('id', $this->report->id));
});

it('rejects invalid report categories and years without changing the report', function (array $fields, string $errorField) {
    $this->actingAs($this->admin)
        ->from(route('content.edit', $this->content))
        ->put(route('content.update', $this->content), ['title' => 'Niet opslaan'] + $fields)
        ->assertRedirect(route('content.edit', $this->content))
        ->assertSessionHasErrors($errorField);

    expect((int) $this->report->fresh()->year)->toBe(2026)
        ->and($this->content->fresh()->title)->toBe('Vis a Vis Carrousel');
})->with([
    'missing year' => [['report-type' => 'year'], 'report-year'],
    'invalid year' => [['report-type' => 'year', 'report-year' => 'geen jaar'], 'report-year'],
    'short year' => [['report-type' => 'year', 'report-year' => 26], 'report-year'],
    'invalid category' => [['report-type' => 'other'], 'report-type'],
]);

it('preserves the category when older edit forms do not submit a report type', function () {
    $this->actingAs($this->admin)
        ->put(route('content.update', $this->content), ['title' => 'Nieuwe titel'])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect((int) $this->report->fresh()->year)->toBe(2026)
        ->and($this->content->fresh()->title)->toBe('Nieuwe titel');
});

it('does not permit report fields on unrelated content', function () {
    $content = Content::create([
        'type' => 'text', 'name' => 'ordinary-content', 'title' => 'Gewone inhoud',
    ]);

    $this->actingAs($this->admin)
        ->put(route('content.update', $content), [
            'title' => 'Niet opslaan', 'report-type' => 'year', 'report-year' => 2026,
        ])
        ->assertSessionHasErrors(['report-type', 'report-year']);

    expect($content->fresh()->title)->toBe('Gewone inhoud');
});

it('prevents members from reclassifying reports', function () {
    $this->actingAs(User::factory()->create())
        ->put(route('content.update', $this->content), [
            'title' => $this->content->title, 'report-type' => 'activity',
        ])
        ->assertRedirect(route('home'));

    expect((int) $this->report->fresh()->year)->toBe(2026);
});