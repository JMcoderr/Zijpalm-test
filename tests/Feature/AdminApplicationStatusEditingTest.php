<?php

use App\ApplicationStatus;
use App\Mail\ActivityApplied;
use App\Mail\ReserveUpgrade;
use App\Models\Activity;
use App\Models\Application;
use App\Models\Content;
use App\Models\Payment;
use App\Models\User;
use App\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mollie\Api\MollieApiClient;
use Mollie\Laravel\Facades\Mollie;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();

    foreach (['email-activiteit-aangemeld', 'email-activiteit-aangemeld-reserve', 'email-reserve-upgrade'] as $name) {
        Content::create([
            'type' => 'email',
            'name' => $name,
            'title' => 'Aanmelding',
            'text' => '',
        ]);
    }

    $this->admin = User::factory()->admin()->create();
    $this->activity = Activity::factory()->create([
        'maxParticipants' => 10,
        'price' => 15,
    ]);
    $this->application = Application::factory()->create([
        'activity_id' => $this->activity->id,
        'user_id' => User::factory()->create()->id,
        'status' => ApplicationStatus::Active,
    ]);
});

it('shows the editing button immediately after adding participants and hides it from members', function () {
    $this->actingAs($this->admin)
        ->get(route('activity.show', $this->activity))
        ->assertOk()
        ->assertSeeInOrder(['Deelnemer toevoegen', 'Aanmeldingen bewerken', 'Verstuur herinnering'])
        ->assertSee('id="edit-applications-form"', false)
        ->assertSee('Wijzigingen opslaan')
        ->assertSee('In afwachting van betaling');

    $this->actingAs($this->application->user)
        ->get(route('activity.show', $this->activity))
        ->assertOk()
        ->assertDontSee('id="edit-applications-form"', false)
        ->assertDontSee('aria-label="Aanmeldingen bewerken"', false);
});

it('corrects five registrations to reserve at once without immediately promoting them or altering payments', function () {
    Mollie::shouldReceive('api')->never();

    $applications = collect([$this->application]);
    for ($i = 0; $i < 4; $i++) {
        $applications->push(Application::factory()->create([
            'activity_id' => $this->activity->id,
            'user_id' => User::factory()->create()->id,
            'status' => ApplicationStatus::Active,
        ]));
    }
    $this->application->update(['participants' => 2]);
    $this->application->guests()->create([
        'firstName' => 'Test',
        'lastName' => 'Guest',
        'adult' => true,
        'email' => 'guest@example.com',
        'phone' => '0612345678',
    ]);
    $payment = $this->application->payments()->create([
        'mollieId' => 'tr_admin_correction',
        'description' => 'Inschrijving',
        'status' => PaymentStatus::paid,
        'price' => 30,
    ]);
    $createdAt = $this->application->created_at;

    $this->actingAs($this->admin)
        ->put(route('application.adminUpdateStatuses', $this->activity), [
            'applications' => $applications->map(fn ($application) => [
                'id' => $application->id,
                'status' => 'reserve',
                'previous_status' => 'active',
            ])->all(),
        ])
        ->assertRedirect(route('activity.show', $this->activity))
        ->assertSessionHas('success', 'De aanmeldingen zijn bijgewerkt (5 gewijzigd).');

    foreach ($applications as $application) {
        expect($application->fresh()->status)->toBe(ApplicationStatus::Reserve);
    }
    expect($this->application->fresh()->created_at->equalTo($createdAt))->toBeTrue()
        ->and($this->application->guests()->count())->toBe(1)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::paid)
        ->and($payment->fresh()->refundedAmount)->toBeNull()
        ->and(Payment::count())->toBe(1);
    Mail::assertNothingSent();

    $this->get(route('activity.show', $this->activity))
        ->assertOk()
        ->assertSee('Aanmeldingen (0/10, 6 reserves)')
        ->assertSee('Reserves (6)');
});

it('allows all three editable statuses without marking payments as paid', function (string $status) {
    $payment = $this->application->payments()->create([
        'mollieId' => 'tr_status_correction',
        'description' => 'Inschrijving',
        'status' => PaymentStatus::pending,
        'price' => 15,
    ]);
    $this->application->update(['status' => ApplicationStatus::Reserve]);

    $this->actingAs($this->admin)
        ->put(route('application.adminUpdateStatuses', $this->activity), [
            'applications' => [['id' => $this->application->id, 'status' => $status, 'previous_status' => 'reserve']],
        ])
        ->assertRedirect(route('activity.show', $this->activity))
        ->assertSessionHas('success');

    expect($this->application->fresh()->status)->toBe(ApplicationStatus::from($status))
        ->and($payment->fresh()->status)->toBe(PaymentStatus::pending)
        ->and($payment->fresh()->paidAt)->toBeNull();
    Mail::assertNothingSent();
})->with(['active', 'reserve', 'pending']);

it('rejects invalid statuses and empty submissions without changing registrations', function (array $entries) {
    $entries = array_map(fn ($entry) => ['id' => $this->application->id, 'previous_status' => 'active'] + $entry, $entries);

    $this->actingAs($this->admin)
        ->from(route('activity.show', $this->activity))
        ->put(route('application.adminUpdateStatuses', $this->activity), ['applications' => $entries])
        ->assertRedirect(route('activity.show', $this->activity))
        ->assertSessionHasErrorsIn('editApplications');

    expect($this->application->fresh()->status)->toBe(ApplicationStatus::Active);
    Mail::assertNothingSent();
})->with([
    'empty' => [[]],
    'unknown status' => [[['status' => 'invalid']]],
    'cancellation requires existing cancellation flow' => [[['status' => 'cancelled']]],
    'missing status' => [[[]]],
    'duplicate registration' => [[['status' => 'reserve'], ['status' => 'pending']]],
]);

it('rejects another activity registration atomically', function () {
    $otherActivity = Activity::factory()->create();
    $otherApplication = Application::factory()->create([
        'activity_id' => $otherActivity->id,
        'user_id' => User::factory()->create()->id,
        'status' => ApplicationStatus::Active,
    ]);

    $this->actingAs($this->admin)
        ->put(route('application.adminUpdateStatuses', $this->activity), [
            'applications' => [
                ['id' => $this->application->id, 'status' => 'reserve', 'previous_status' => 'active'],
                ['id' => $otherApplication->id, 'status' => 'reserve', 'previous_status' => 'active'],
            ],
        ])
        ->assertSessionHasErrorsIn('editApplications');

    expect($this->application->fresh()->status)->toBe(ApplicationStatus::Active)
        ->and($otherApplication->fresh()->status)->toBe(ApplicationStatus::Active);
});

it('does not reinstate cancelled registrations', function () {
    $this->application->update(['status' => ApplicationStatus::Cancelled]);

    $this->actingAs($this->admin)
        ->put(route('application.adminUpdateStatuses', $this->activity), [
            'applications' => [['id' => $this->application->id, 'status' => 'active', 'previous_status' => 'reserve']],
        ])
        ->assertSessionHasErrorsIn('editApplications');

    expect($this->application->fresh()->status)->toBe(ApplicationStatus::Cancelled);
});

it('prevents regular members and unauthenticated visitors from changing statuses', function () {
    $payload = ['applications' => [['id' => $this->application->id, 'status' => 'reserve']]];

    $this->put(route('application.adminUpdateStatuses', $this->activity), $payload)
        ->assertRedirect(route('login'));
    $this->actingAs($this->application->user)
        ->put(route('application.adminUpdateStatuses', $this->activity), $payload)
        ->assertRedirect(route('home'));

    expect($this->application->fresh()->status)->toBe(ApplicationStatus::Active);
});

it('does not overwrite concurrent status changes and rolls back the entire correction', function () {
    $otherApplication = Application::factory()->create([
        'activity_id' => $this->activity->id,
        'user_id' => User::factory()->create()->id,
        'status' => ApplicationStatus::Active,
    ]);

    $this->actingAs($this->admin)
        ->put(route('application.adminUpdateStatuses', $this->activity), [
            'applications' => [
                ['id' => $otherApplication->id, 'status' => 'reserve', 'previous_status' => 'active'],
                ['id' => $this->application->id, 'status' => 'reserve', 'previous_status' => 'pending'],
            ],
        ])
        ->assertSessionHasErrorsIn('editApplications', 'applications');

    expect($this->application->fresh()->status)->toBe(ApplicationStatus::Active)
        ->and($otherApplication->fresh()->status)->toBe(ApplicationStatus::Active);
});

it('reports unchanged submissions without affecting registrations', function () {
    $this->actingAs($this->admin)
        ->put(route('application.adminUpdateStatuses', $this->activity), [
            'applications' => [['id' => $this->application->id, 'status' => 'active', 'previous_status' => 'active']],
        ])
        ->assertRedirect(route('activity.show', $this->activity))
        ->assertSessionHas('success', 'Er zijn geen statussen gewijzigd.');

    expect($this->application->fresh()->status)->toBe(ApplicationStatus::Active);
    Mail::assertNothingSent();
});

it('keeps corrected reserves on the waiting list when an old payment updates', function (PaymentStatus $status) {
    $this->application->update(['status' => ApplicationStatus::Reserve]);
    $payment = $this->application->payments()->create([
        'mollieId' => 'tr_old_link',
        'description' => 'Inschrijving',
        'status' => $status,
        'price' => 15,
    ]);

    $this->application->handleNewStatus($payment);

    expect($this->application->fresh()->status)->toBe(ApplicationStatus::Reserve);
    Mail::assertNothingSent();
})->with([PaymentStatus::paid, PaymentStatus::expired, PaymentStatus::failed, PaymentStatus::canceled]);

it('promotes a previously paid reserve without asking them to pay twice', function () {
    Mollie::shouldReceive('api')->never();
    $this->application->update(['status' => ApplicationStatus::Reserve]);
    $this->application->payments()->create([
        'mollieId' => 'tr_already_paid',
        'description' => 'Inschrijving',
        'status' => PaymentStatus::paid,
        'price' => 15,
    ]);

    $this->activity->updateApplications();

    expect($this->application->fresh()->status)->toBe(ApplicationStatus::Active);
    Mail::assertSent(ActivityApplied::class);
    Mail::assertNotSent(ReserveUpgrade::class);
});

it('charges only the outstanding amount when a partially paid reserve is promoted', function () {
    $this->application->update(['status' => ApplicationStatus::Reserve]);
    $paidPayment = $this->application->payments()->create([
        'mollieId' => 'tr_partial_payment',
        'description' => 'Inschrijving',
        'status' => PaymentStatus::paid,
        'price' => 10,
    ]);
    $paidPayment->refundedAmount = 2;
    $paidPayment->save();

    $this->activity->updateApplications();
    expect($this->application->fresh()->status)->toBe(ApplicationStatus::Pending);
    Mail::assertSent(ReserveUpgrade::class);

    $endpoint = Mockery::mock();
    $endpoint->shouldReceive('create')->once()->with(Mockery::on(
        fn ($data) => $data['amount'] === ['currency' => 'EUR', 'value' => '7.00']
    ))->andReturn((object) ['id' => 'pl_remaining']);
    $endpoint->shouldReceive('get')->once()->with('pl_remaining')->andReturn((object) [
        '_links' => (object) ['paymentLink' => (object) ['href' => 'https://example.com/pay']],
    ]);
    $client = Mockery::mock(MollieApiClient::class);
    $client->paymentLinks = $endpoint;
    Mollie::shouldReceive('api')->twice()->andReturn($client);

    $mail = new ReserveUpgrade($this->application->fresh());
    $mail->content();

    expect($mail->totalCost)->toBe(7.0)
        ->and($this->application->payments()->where('mollieId', 'pl_remaining')->firstOrFail()->getPrice())
        ->toEqual(7);
});
