<?php

use App\Models\Activity;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    \App\Models\Content::firstOrCreate(
        ['name' => 'background'],
        ['type' => 'image', 'filePath' => 'images/bg.jpg', 'fileType' => 'image', 'title' => 'BG']
    );
});

// Feature: Home page loads and shows banner
it('loads the homepage and shows banner', function () {
    // create required content entries used by the homepage
    \App\Models\Content::create(['type' => 'text', 'name' => 'homepage-banner', 'title' => 'Banner Title', 'text' => 'Welcome']);
    \App\Models\Content::create(['type' => 'text', 'name' => 'homepage-info', 'title' => 'Info Title', 'text' => 'Info']);
    \App\Models\Content::create(['type' => 'text', 'name' => 'homepage-activity-idea', 'title' => 'Idea Title', 'text' => 'Idea']);

    $this->get('/')
        ->assertStatus(200)
        ->assertSee('Banner Title');
});

// Feature: Activities index loads and lists activities
it('shows the activities index with activities', function () {
    $activity = Activity::factory()->create(['title' => 'Index Activity']);

    $this->get(route('activity.index'))
        ->assertStatus(200)
        ->assertSee('Index Activity');
});

// Feature: Activity details page is accessible
it('shows an activity details page', function () {
    $activity = Activity::factory()->create(['title' => 'Show Activity']);

    $this->get(route('activity.show', $activity))
        ->assertStatus(200)
        ->assertSee('Show Activity');
});

// Feature: Admin can download participants export
it('allows admin to download participants export', function () {
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


