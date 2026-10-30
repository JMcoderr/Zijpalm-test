<?php

use App\ApplicationStatus;
use App\Models\Activity;
use App\Models\Application;
use App\Models\Content;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mollie\Api\MollieApiClient;
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

it('allows an introducee at an unlimited free activity and confirms both participants without Mollie', function () {
    Mail::fake();
    Mollie::shouldReceive('api')->never();
    $activity = Activity::factory()->create([
        'maxParticipants' => null, 'maxGuests' => 1, 'price' => 0,
        'type' => \App\ActivityType::OneDay,
    ]);
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('activity.show', $activity))->assertOk()->assertSee('Introducees');
    \Livewire\Livewire::test(\App\Livewire\GuestBuilder::class, ['activity' => $activity])
        ->call('addGuest')->assertCount('guests', 1)->call('addGuest')->assertCount('guests', 1);
    $this->post(route('application.store', $activity), [
        'email' => $user->email, 'phone' => '0612345678',
        'guests' => [['firstName' => 'Test', 'lastName' => 'Introducee', 'phone' => '0687654321', 'email' => 'intro@example.com']],
    ])->assertSessionHasNoErrors()->assertSessionHas('success');
    $application = $activity->applications()->sole();
    expect($application->status)->toBe(ApplicationStatus::Active)->and($application->participants)->toBe(2)
        ->and($application->guests()->sole()->email)->toBe('intro@example.com')
        ->and($application->payments()->count())->toBe(0);
});

it('rejects introducees above the configured activity limit', function () {
    $activity = Activity::factory()->create(['maxGuests' => 0]);
    $user = User::factory()->create();
    $this->actingAs($user)->post(route('application.store', $activity), [
        'email' => $user->email, 'phone' => '0612345678',
        'guests' => [['firstName' => 'Test', 'lastName' => 'Introducee', 'phone' => '0687654321', 'email' => 'intro@example.com']],
    ])->assertSessionHasErrors('guests', null, 'signupActivity');
    expect($activity->applications()->count())->toBe(0);
});

it('recalculates signup costs with a deleted participant account', function (bool $isOrganizer) {
    $deletedUser = User::factory()->create(['firstName' => 'Former', 'lastName' => 'Organizer']);
    $user = User::factory()->create(['firstName' => 'Current', 'lastName' => 'Member']);
    $activity = Activity::factory()->create([
        'type' => \App\ActivityType::OneDay,
        'maxParticipants' => null,
        'maxGuests' => 1,
        'price' => 25,
        'organizer' => $deletedUser->name . ($isOrganizer ? ', ' . $user->name : ''),
        'free_organizer_count' => 1,
    ]);
    Application::factory()->create([
        'activity_id' => $activity->id,
        'user_id' => $deletedUser->id,
        'status' => ApplicationStatus::Active,
    ]);
    $deletedUser->delete();

    $this->actingAs($user);
    \Livewire\Livewire::test(\App\Livewire\ApplicationForm::class, ['activity' => $activity->fresh()])
        ->assertSet('costs.base', $isOrganizer ? 0 : 25)
        ->dispatch('guestsUpdated', 1)
        ->assertSet('participants', 2)
        ->assertSet('costs.base', $isOrganizer ? 25 : 50)
        ->assertSet('costs.total', $isOrganizer ? 25 : 50);
})->with(['regular member' => false, 'free organizer' => true]);

it('reaches checkout despite a deleted participant account', function (bool $isOrganizer, bool $withGuest, bool $afterCancellation) {
    Mail::fake();
    $deletedUser = User::factory()->create(['firstName' => 'Former', 'lastName' => 'Organizer']);
    $user = User::factory()->create(['firstName' => 'Current', 'lastName' => 'Member']);
    $activity = Activity::factory()->create([
        'type' => \App\ActivityType::OneDay,
        'maxParticipants' => null,
        'maxGuests' => 1,
        'price' => 25,
        'organizer' => $deletedUser->name . ($isOrganizer ? ', ' . $user->name : ''),
        'free_organizer_count' => 1,
    ]);
    $existingApplication = Application::factory()->create([
        'activity_id' => $activity->id,
        'user_id' => $deletedUser->id,
        'status' => ApplicationStatus::Active,
    ]);
    $deletedUser->delete();
    $cancelledApplication = $afterCancellation ? Application::factory()->create([
        'activity_id' => $activity->id,
        'user_id' => $user->id,
        'status' => ApplicationStatus::Cancelled,
    ]) : null;
    $participants = $withGuest ? 2 : 1;
    $price = ($participants - ($isOrganizer ? 1 : 0)) * 25;
    $endpoint = Mockery::mock();
    $endpoint->shouldReceive('create')->once()->with(Mockery::on(
        fn ($data) => $data['amount'] === ['currency' => 'EUR', 'value' => number_format($price, 2, '.', '')]
    ))->andReturn((object) ['id' => 'tr_signup_regression']);
    $endpoint->shouldReceive('get')->once()->with('tr_signup_regression')->andReturn((object) [
        '_links' => (object) ['checkout' => (object) ['href' => 'https://example.com/checkout']],
    ]);
    $client = Mockery::mock(MollieApiClient::class);
    $client->payments = $endpoint;
    Mollie::shouldReceive('api')->twice()->andReturn($client);

    $this->actingAs($user)->post(route('application.store', $activity), [
        'email' => $user->email,
        'phone' => '0612345678',
        'guests' => $withGuest ? [[
            'firstName' => 'Test', 'lastName' => 'Introducee',
            'phone' => '0687654321', 'email' => 'intro@example.com',
        ]] : [],
    ])->assertSessionHasNoErrors()->assertStatus(303)->assertRedirect('https://example.com/checkout');

    $application = $activity->applications()->where('user_id', $user->id)
        ->where('status', '!=', ApplicationStatus::Cancelled)->sole();
    expect($application->participants)->toBe($participants)
        ->and($application->guests()->count())->toBe($withGuest ? 1 : 0)
        ->and($application->payments()->sole()->price)->toBe(number_format($price, 2, '.', ''))
        ->and($application->payments()->sole()->status)->toBe(\App\PaymentStatus::pending)
        ->and($existingApplication->fresh()->status)->toBe(ApplicationStatus::Active);
    if ($cancelledApplication) {
        expect($cancelledApplication->fresh()->status)->toBe(ApplicationStatus::Cancelled);
    }
    Mail::assertNothingSent();
})->with([
    'member with introducee' => [false, true, false],
    'organizer with introducee' => [true, true, false],
    'member alone' => [false, false, false],
    'registration after cancellation' => [false, false, true],
]);

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
