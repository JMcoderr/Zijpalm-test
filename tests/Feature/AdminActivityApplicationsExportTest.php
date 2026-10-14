<?php

use App\ApplicationStatus;
use App\Models\Activity;
use App\Models\Application;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;

uses(RefreshDatabase::class);

it('downloads the participant workbook when a linked member account is missing', function () {
    $activity = Activity::factory()->create(['maxParticipants' => 10]);
    $user = User::factory()->create();
    $application = Application::factory()->create([
        'activity_id' => $activity->id,
        'user_id' => $user->id,
        'status' => ApplicationStatus::Active,
        'email' => 'participant@example.test',
        'phone' => '0612345678',
    ]);

    $application->setRelation('user', null);
    $activity->setRelation('applications', collect([$application]));

    $export = $activity->createApplicationsExcelFile();
    $spreadsheet = null;

    try {
        $spreadsheet = IOFactory::load($export['filePath']);
        $sheet = $spreadsheet->getSheetByName('Aanmeldingen');

        expect($export['fileName'])->toBe('aanmeldingen_' . $activity->id . '.xlsx')
            ->and($sheet->getCell('B3')->getValue())->toBe('Lid (account ontbreekt)')
            ->and($sheet->getCell('C3')->getValue())->toBe('Account niet gevonden')
            ->and($sheet->getCell('D3')->getValue())->toBe('participant@example.test')
            ->and($sheet->getCell('E3')->getValue())->toBe('0612345678');
    } finally {
        $spreadsheet?->disconnectWorksheets();
        if (isset($export['filePath']) && is_file($export['filePath'])) {
            unlink($export['filePath']);
        }
    }
});

it('serves the participant workbook through the admin download route', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $activity = Activity::factory()->create(['maxParticipants' => 10]);
    $user = User::factory()->create();
    Application::factory()->create([
        'activity_id' => $activity->id,
        'user_id' => $user->id,
        'status' => ApplicationStatus::Active,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.activities.download', ['activity' => $activity->id]))
        ->assertOk()
        ->assertDownload('aanmeldingen_' . $activity->id . '.xlsx');

    $tempFile = $response->baseResponse->getFile()->getPathname();
    if (is_file($tempFile)) {
        unlink($tempFile);
    }
});
