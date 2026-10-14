<?php

use App\ApplicationStatus;
use App\Models\Activity;
use App\Models\Application;
use App\Models\Content;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mollie\Laravel\Facades\Mollie;

uses(RefreshDatabase::class);

beforeEach(function () {
    Content::create([
        'type' => 'email',
        'name' => 'email-activiteit-aangemeld',
        'title' => 'Aanmelding',
        'text' => '',
    ]);
    Content::create([
        'type' => 'email',
        'name' => 'email-activiteit-aangemeld-reserve',
        'title' => 'Reservereserve',
        'text' => '',
    ]);
});

it('puts signups on the reserve list when existing guests fill the activity and skips Mollie', function () {
    Mail::fake();
    Mollie::shouldReceive('api')->never();

    $activity = Activity::factory()->create([
        'maxParticipants' => 2,
        'price' => 15,
    ]);
    $existingUser = User::factory()->create();
    Application::factory()->create([
        'activity_id' => $activity->id,
        'user_id' => $existingUser->id,
        'participants' => 2,
        'status' => ApplicationStatus::Active,
    ]);
    $newUser = User::factory()->create();

    $this->actingAs($newUser)
        ->post(route('application.store', $activity), [
            'email' => $newUser->email,
            'phone' => '0612345678',
        ])
        ->assertRedirect(route('activity.show', $activity))
        ->assertSessionHas('success', "U bent succesvol ingeschreven als reserve voor '{$activity->title}'");

    $application = Application::query()
        ->where('activity_id', $activity->id)
        ->where('user_id', $newUser->id)
        ->firstOrFail();

    expect($application->status)->toBe(ApplicationStatus::Reserve)
        ->and($application->payments()->count())->toBe(0);
});

it('keeps reserve participants separate from active capacity and the admin registration total', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $activity = Activity::factory()->create([
        'maxParticipants' => 2,
        'price' => 0,
    ]);
    $activeUser = User::factory()->create();
    $reserveUser = User::factory()->create();
    Application::factory()->create([
        'activity_id' => $activity->id,
        'user_id' => $activeUser->id,
        'participants' => 1,
        'status' => ApplicationStatus::Active,
    ]);
    Application::factory()->create([
        'activity_id' => $activity->id,
        'user_id' => $reserveUser->id,
        'participants' => 1,
        'status' => ApplicationStatus::Reserve,
    ]);

    $newUser = User::factory()->create();
    $this->actingAs($newUser)
        ->post(route('application.store', $activity), [
            'email' => $newUser->email,
            'phone' => '0612345678',
        ])
        ->assertRedirect(route('activity.show', $activity));

    expect(Application::query()->where('activity_id', $activity->id)->where('user_id', $newUser->id)->firstOrFail()->status)
        ->toBe(ApplicationStatus::Active);

    $this->actingAs($admin)
        ->get(route('activity.show', $activity))
        ->assertOk()
        ->assertSee('Aanmeldingen (2/2, 1 reserve)')
        ->assertSee('Reserves (1)');
});
