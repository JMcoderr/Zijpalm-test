<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Ods;

uses(RefreshDatabase::class);

function makeMembershipStartDateOds(array $rows): string
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray(['Mailadres', 'Ingangsdatum', 'email'], null, 'A1');

    foreach ($rows as $index => $row) {
        $rowNumber = $index + 2;
        $sheet->setCellValue("A{$rowNumber}", $row['mailadres'] ?? $row['email'] ?? '');
        $sheet->setCellValue("B{$rowNumber}", $row['date'] ?? null);
        $sheet->setCellValue("C{$rowNumber}", $row['email'] ?? $row['mailadres'] ?? '');
    }

    $path = tempnam(sys_get_temp_dir(), 'membership-start-dates-');
    if ($path === false) {
        throw new RuntimeException('Could not create a temporary ODS file.');
    }
    unlink($path);
    $path .= '.ods';

    (new Ods($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();

    return $path;
}

function removeMembershipStartDateFile(string $path): void
{
    if (is_file($path)) {
        unlink($path);
    }
}

it('previews spreadsheet dates without writing them', function () {
    $user = User::factory()->create(['email' => 'start-date@example.test']);
    $path = makeMembershipStartDateOds([
        ['email' => 'start-date@example.test', 'date' => '2024-03-15'],
    ]);

    try {
        $this->artisan('members:import-start-dates', ['file' => $path])
            ->expectsOutputToContain('Preview only')
            ->assertSuccessful();

        expect($user->fresh()->membership_start_date)->toBeNull();
    } finally {
        removeMembershipStartDateFile($path);
    }
});

it('applies dates from ODS and leaves blank spreadsheet dates unchanged', function () {
    $toUpdate = User::factory()->create(['email' => 'to-update@example.test']);
    $toPreserve = User::factory()->create([
        'email' => 'to-preserve@example.test',
        'membership_start_date' => '2020-01-02',
    ]);
    $path = makeMembershipStartDateOds([
        ['email' => 'to-update@example.test', 'date' => '2024-03-15'],
        ['email' => 'to-preserve@example.test', 'date' => ''],
    ]);

    try {
        $this->artisan('members:import-start-dates', ['file' => $path, '--apply' => true])
            ->expectsOutputToContain('Updated 1 membership start date(s).')
            ->assertSuccessful();

        expect($toUpdate->fresh()->membership_start_date?->format('Y-m-d'))->toBe('2024-03-15')
            ->and($toPreserve->fresh()->membership_start_date?->format('Y-m-d'))->toBe('2020-01-02');
    } finally {
        removeMembershipStartDateFile($path);
    }
});

it('does not partially apply when an email address does not match a user', function () {
    $user = User::factory()->create(['email' => 'valid-match@example.test']);
    $path = makeMembershipStartDateOds([
        ['email' => 'valid-match@example.test', 'date' => '2024-03-15'],
        ['email' => 'missing-user@example.test', 'date' => '2024-04-20'],
    ]);

    try {
        $this->artisan('members:import-start-dates', ['file' => $path, '--apply' => true])
            ->expectsOutputToContain('No changes were written')
            ->assertFailed();

        expect($user->fresh()->membership_start_date)->toBeNull();
    } finally {
        removeMembershipStartDateFile($path);
    }
});

it('keeps an existing date unless overwrite is explicitly requested', function () {
    $user = User::factory()->create([
        'email' => 'existing-date@example.test',
        'membership_start_date' => '2020-01-02',
    ]);
    $path = makeMembershipStartDateOds([
        ['email' => 'existing-date@example.test', 'date' => '2024-03-15'],
    ]);

    try {
        $this->artisan('members:import-start-dates', ['file' => $path, '--apply' => true])
            ->expectsOutputToContain('Updated 0 membership start date(s).')
            ->assertSuccessful();
        expect($user->fresh()->membership_start_date?->format('Y-m-d'))->toBe('2020-01-02');

        $this->artisan('members:import-start-dates', [
            'file' => $path,
            '--apply' => true,
            '--overwrite' => true,
        ])->expectsOutputToContain('Updated 1 membership start date(s).')
            ->assertSuccessful();

        expect($user->fresh()->membership_start_date?->format('Y-m-d'))->toBe('2024-03-15');
    } finally {
        removeMembershipStartDateFile($path);
    }
});

it('matches all configured user email fields and rejects contradictory sheet emails', function () {
    $user = User::factory()->create([
        'email' => 'primary@example.test',
        'emailSecondary' => 'secondary@example.test',
    ]);
    $path = makeMembershipStartDateOds([
        ['mailadres' => 'secondary@example.test', 'email' => 'secondary@example.test', 'date' => '2024-03-15'],
    ]);

    try {
        $this->artisan('members:import-start-dates', ['file' => $path, '--apply' => true])
            ->expectsOutputToContain('Updated 1 membership start date(s).')
            ->assertSuccessful();
        expect($user->fresh()->membership_start_date?->format('Y-m-d'))->toBe('2024-03-15');
    } finally {
        removeMembershipStartDateFile($path);
    }

    $path = makeMembershipStartDateOds([
        ['mailadres' => 'one@example.test', 'email' => 'two@example.test', 'date' => '2024-03-15'],
    ]);

    try {
        $this->artisan('members:import-start-dates', ['file' => $path])
            ->expectsOutputToContain('conflicting email addresses')
            ->assertFailed();
    } finally {
        removeMembershipStartDateFile($path);
    }
});
