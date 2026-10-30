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
        ->assertSee('grid grid-cols-1 lg:grid-cols-2 gap-5 w-full items-start', false)
        ->assertSee('data-activity-image', false)->assertSee('relative aspect-video w-full shrink-0 overflow-hidden bg-white', false)
        ->assertSee('absolute inset-0 size-full object-contain object-center', false)
        ->assertSee('data-activity-details', false)
        ->assertSee('mb-2 flex flex-wrap items-center justify-between gap-2', false)
        ->assertSee('id="edit-applications-form"', false)
        ->assertSee('Wijzigingen opslaan')
        ->assertSee('In afwachting van betaling');

    $this->actingAs($this->application->user)
        ->get(route('activity.show', $this->activity))
        ->assertOk()
        ->assertDontSee('id="edit-applications-form"', false)
        ->assertDontSee('aria-label="Aanmeldingen bewerken"', false);
});

it('shows complete activity card photos in a white widescreen frame', function () {
    $html = \Illuminate\Support\Facades\Blade::render(
        '<x-zijpalm-card :content="$activity" type="activity" />',
        ['activity' => $this->activity],
    );

    expect($html)->toContain('data-activity-card-image')
        ->toContain('relative aspect-video w-full shrink-0 bg-white')
        ->toContain('absolute inset-0 size-full object-contain object-center')
        ->toContain('self-start')
        ->not->toContain('object-cover')
        ->not->toContain('aspect-5/6')
        ->not->toContain('sm:flex-2/5');
});

it('removes a reserve whose account is missing without failing after cancellation', function () {
    Content::create([
        'type' => 'email', 'name' => 'email-activiteit-afgemeld',
        'title' => 'Afmelding', 'text' => 'Je bent afgemeld.',
    ]);
    $this->application->update(['status' => ApplicationStatus::Reserve]);
    $this->application->user->delete();

    $this->actingAs($this->admin)
        ->from(route('activity.show', $this->activity))
        ->delete(route('application.destroy', $this->application))
        ->assertRedirect(route('activity.show', $this->activity))
        ->assertSessionHas('success');

    expect($this->application->fresh()->status)->toBe(ApplicationStatus::Cancelled);
    Mail::assertSent(\App\Mail\ApplicationCancelled::class, function ($mail) {
        $body = json_decode($mail->content()->with['jsonBody'], true, flags: JSON_THROW_ON_ERROR);

        return $body['email'] === $this->application->user()->withTrashed()->first()->email;
    });
});

it('renders cancellation mail using the registration email when no account exists', function () {
    Content::create([
        'type' => 'email', 'name' => 'email-activiteit-afgemeld',
        'title' => 'Afmelding', 'text' => 'Je bent afgemeld.',
    ]);
    $this->application->user_id = null;
    $mail = new \App\Mail\ApplicationCancelled($this->application);
    $body = json_decode($mail->content()->with['jsonBody'], true, flags: JSON_THROW_ON_ERROR);

    expect($mail->user)->toBeNull()
        ->and($body['email'])->toBe($this->application->email)
        ->and($body['emails'])->toBe([$this->application->email])
        ->and($body['body'])->toContain('Je bent afgemeld.');
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
        ->from(route('activity.show', $this->activity))
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

it('edits application contact details and adds named introducees to the participant list', function () {
    $this->application->update(['participants' => 2]);
    $guest = $this->application->guests()->create([
        'firstName' => 'Oude',
        'lastName' => 'Introducee',
        'email' => 'old-guest@example.com',
        'phone' => '0611111111',
        'adult' => true,
    ]);

    $this->actingAs($this->admin)
        ->put(route('application.adminUpdateStatuses', $this->activity), [
            'applications' => [[
                'id' => $this->application->id,
                'status' => 'active',
                'previous_status' => 'active',
                'email' => 'updated-member@example.com',
                'phone' => '0622222222',
                'comment' => 'Komt met twee introducees.',
                'guests' => [[
                    'id' => $guest->id,
                    'firstName' => 'Nieuwe',
                    'lastName' => 'Naam',
                    'email' => 'updated-guest@example.com',
                    'phone' => '0633333333',
                    'adult' => '1',
                ]],
                'new_guests' => [[
                    'firstName' => 'Extra',
                    'lastName' => 'Gast',
                    'email' => 'extra-guest@example.com',
                    'phone' => '0644444444',
                    'adult' => '1',
                ], [
                    'firstName' => 'Naam',
                    'lastName' => 'Onbekend',
                ]],
            ]],
        ])
        ->assertRedirect(route('activity.show', $this->activity))
        ->assertSessionHas('success');

    expect($this->application->fresh()->email)->toBe('updated-member@example.com')
        ->and($this->application->fresh()->phone)->toBe('0622222222')
        ->and($this->application->fresh()->comment)->toBe('Komt met twee introducees.')
        ->and($this->application->fresh()->participants)->toBe(4)
        ->and($guest->fresh()->name)->toBe('Nieuwe Naam')
        ->and($this->application->guests()->count())->toBe(3)
        ->and($this->application->guests()->where('firstName', 'Naam')->value('email'))->toBe('');

    $this->get(route('activity.show', $this->activity))
        ->assertOk()
        ->assertSee('Nieuwe Naam')
        ->assertSee('updated-guest@example.com')
        ->assertSee('Extra Gast')
        ->assertSee('extra-guest@example.com')
        ->assertSee('Naam Onbekend');
});

it('does not allow an admin to edit an introducee from another application', function () {
    $otherApplication = Application::factory()->create([
        'activity_id' => $this->activity->id,
        'user_id' => User::factory()->create()->id,
        'status' => ApplicationStatus::Active,
    ]);
    $otherGuest = $otherApplication->guests()->create([
        'firstName' => 'Andere',
        'lastName' => 'Gast',
        'email' => 'other-guest@example.com',
        'phone' => '0612345678',
        'adult' => true,
    ]);

    $this->actingAs($this->admin)
        ->from(route('activity.show', $this->activity))
        ->put(route('application.adminUpdateStatuses', $this->activity), [
            'applications' => [[
                'id' => $this->application->id,
                'status' => 'active',
                'previous_status' => 'active',
                'guests' => [[
                    'id' => $otherGuest->id,
                    'firstName' => 'Gewijzigd',
                    'lastName' => 'Onterecht',
                    'email' => 'changed@example.com',
                    'phone' => '0699999999',
                ]],
            ]],
        ])
        ->assertRedirect(route('activity.show', $this->activity))
        ->assertSessionHasErrorsIn('editApplications', 'applications');

    expect($otherGuest->fresh()->name)->toBe('Andere Gast')
        ->and($otherGuest->fresh()->email)->toBe('other-guest@example.com');
});

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
