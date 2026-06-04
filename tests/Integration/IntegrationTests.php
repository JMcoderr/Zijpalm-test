<?php

use App\Models\Activity;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// Integration: Admin can create an activity and is redirected
it('maakt een activiteit aan via admin en redirect', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $image = UploadedFile::fake()->image('cover.jpg');

    $this->actingAs($admin)
        ->post(route('activity.store'), [
            'title' => 'Test activiteit',
            'start-date' => now()->addDays(7)->toDateString(),
            'start-time' => '10:00',
            'end-date' => now()->addDays(7)->toDateString(),
            'end-time' => '12:00',
            'location' => 'Zaal A',
            'price' => '0.00',
            'organizer' => 'Ik',
            'maxParticipants' => 20,
            'maxGuests' => 0,
            'registrationStart' => now()->toDateString(),
            'registrationEnd' => now()->addDays(6)->toDateString(),
            'description' => 'Korte beschrijving',
            'image-upload' => $image,
            'free_organizer_count' => 0,
            'whatsappUrl' => null,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('activities', ['title' => 'Test activiteit']);
});

// Integration: Admin can import members via CSV
it('importeert leden via CSV', function () {
    Storage::fake('local');
    $admin = User::factory()->create(['is_admin' => true]);

    $csv = "voornaam,achternaam,mailadres,functie,telefoon\nJo,Tester,jo@example.com,gepensioneerd,0612345678\n";
    $file = UploadedFile::fake()->createWithContent('members.csv', $csv);

    $this->actingAs($admin)
        ->post(route('admin.importMembers'), ['import-members-form-members-list' => $file])
        ->assertSessionHas('success');

    $this->assertDatabaseHas('users', ['email' => 'jo@example.com']);
});

// Feature: Public can view activity details
it('shows activity details page', function () {
    $activity = Activity::factory()->create(['title' => 'Public Activity']);

    \App\Models\Content::create([
        'type' => 'image',
        'name' => 'background',
        'filePath' => 'images/bg.jpg',
        'fileType' => 'image',
        'title' => 'Background'
    ]);

    $this->get(route('activity.show', $activity))
        ->assertStatus(200)
        ->assertSee('Public Activity');
});

// Integration: Admin can export participants list as a downloadable file
it('exporteert deelnemerslijst en geeft bestand', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $activity = Activity::factory()->create(['maxParticipants' => 10]);

    $user = User::factory()->create();
    $application = \App\Models\Application::factory()->create(['activity_id' => $activity->id, 'user_id' => $user->id, 'status' => \App\ApplicationStatus::Active]);
    Payment::factory()->create(['application_id' => $application->id, 'status' => \App\PaymentStatus::paid, 'price' => 10.00]);

    $this->actingAs($admin)
        ->get(route('admin.activities.download', ['activity' => $activity->id]))
        ->assertStatus(200)
        ->assertHeader('content-disposition');
});


