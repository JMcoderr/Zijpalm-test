<?php

use App\ApplicationStatus;
use App\Models\Activity;
use App\Models\Application;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\User;
use App\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
    $this->admin = User::factory()->admin()->create();
    $this->organizer = User::factory()->create();
    $this->activity = Activity::factory()->create();
});

it('lets only admins assign organizer accounts and revoke access', function () {
    $this->actingAs($this->admin)
        ->get(route('activity.edit', $this->activity))
        ->assertOk()->assertSee('Medeorganisatoren')
        ->assertSee('name="organizer_user_ids[]"', false);

    $this->put(route('activity.update', $this->activity), [
        'end-date' => $this->activity->end->toDateString(),
        'organizer_user_ids_present' => 1,
        'organizer_user_ids' => [$this->organizer->id],
    ])->assertSessionHasNoErrors()->assertRedirect();

    expect($this->activity->fresh()->organizer_user_ids)->toBe([$this->organizer->id]);

    $this->actingAs($this->organizer)
        ->put(route('activity.update', $this->activity), [
            'organizer_user_ids' => [$this->organizer->id],
        ])->assertRedirect(route('home'));

    $this->actingAs($this->admin)
        ->put(route('activity.update', $this->activity), [
            'end-date' => now()->addMonth()->toDateString(),
            'organizer_user_ids_present' => 1,
        ])->assertSessionHasNoErrors()->assertRedirect();

    expect($this->activity->fresh()->organizer_user_ids)->toBe([]);

    $this->actingAs($this->organizer)
        ->get(route('activity.participants.download', $this->activity))->assertForbidden();
});

it('preserves assigned accounts when an older admin form omits the selection', function () {
    $this->activity->update(['organizer_user_ids' => [$this->organizer->id]]);

    $this->actingAs($this->admin)->put(route('activity.update', $this->activity), [
        'end-date' => $this->activity->end->toDateString(),
    ])->assertSessionHasNoErrors()->assertRedirect();

    expect($this->activity->fresh()->organizer_user_ids)->toBe([$this->organizer->id]);
});

it('rejects nonexistent organizer accounts', function () {
    $this->actingAs($this->admin)->put(route('activity.update', $this->activity), [
        'end-date' => $this->activity->end->toDateString(),
        'organizer_user_ids' => [99999999],
    ])->assertSessionHasErrors('organizer_user_ids.0');

    expect($this->activity->fresh()->organizer_user_ids)->toBeNull();
});

it('shows assigned organizers only the read-only list without payment or management information', function () {
    $this->activity->update(['organizer_user_ids' => [$this->organizer->id], 'maxParticipants' => 36]);
    $application = Application::factory()->create([
        'activity_id' => $this->activity->id,
        'user_id' => User::factory()->create()->id,
        'status' => ApplicationStatus::Active,
        'email' => 'participant@example.test',
        'comment' => 'INTERNAL_PAYMENT_NOTE',
        'participants' => 2,
    ]);
    $application->guests()->save(Guest::factory()->make(['email' => 'guest@example.test']));
    Payment::factory()->create([
        'application_id' => $application->id,
        'status' => PaymentStatus::paid,
        'price' => 98765.43,
        'mollieId' => 'tr_PRIVATE_PAYMENT',
    ]);

    $response = $this->actingAs($this->organizer)->get(route('activity.show', $this->activity))
        ->assertOk()->assertSee('Aanmeldingen (2/36)')->assertSee('participant@example.test')
        ->assertSee('guest@example.test')->assertSee(route('activity.participants.download', $this->activity), false)
        ->assertDontSee('Kostenoverzicht')->assertDontSee('INTERNAL_PAYMENT_NOTE')
        ->assertDontSee('98765')->assertDontSee('tr_PRIVATE_PAYMENT')
        ->assertDontSee('In afwachting van betaling')->assertDontSee('Deelnemer toevoegen')
        ->assertDontSee('Aanmeldingen bewerken')->assertDontSee('Verstuur herinnering')
        ->assertDontSee('Bewerk activiteit')->assertDontSee('Verwijder van deelnemerslijst')
        ->assertDontSee(route('admin.activities.download', $this->activity), false);

    $document = new DOMDocument();
    $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    $list = $xpath->query('//*[@data-participant-list]')->item(0);
    expect($xpath->query('.//a', $list))->toHaveCount(1)
        ->and($xpath->query('.//button | .//form', $list))->toHaveCount(0)
        ->and($xpath->query('.//*[@name="List"]', $list))->toHaveCount(1)
        ->and($list->textContent)->toContain('Datum', 'Naam', 'Telefoon', 'Mail');

    $participant = $response->viewData('participantApplications')->first();
    expect($participant->getAttributes())->not->toHaveKey('comment')->not->toHaveKey('shop_unit_price')
        ->and($participant->relationLoaded('payments'))->toBeFalse();
});

it('denies list access to other members and to organizers of a different activity', function () {
    $this->activity->update(['organizer_user_ids' => [$this->organizer->id]]);
    $other = Activity::factory()->create(['organizer' => $this->organizer->name]);

    $this->actingAs(User::factory()->create())
        ->get(route('activity.show', $this->activity))->assertOk()->assertDontSee('data-participant-list', false);
    $this->get(route('activity.participants.download', $this->activity))->assertForbidden();

    $this->actingAs($this->organizer)
        ->get(route('activity.show', $other))->assertOk()->assertDontSee('data-participant-list', false);
    $this->get(route('activity.participants.download', $other))->assertForbidden();
    $this->get(route('admin.activities.download', $this->activity))->assertRedirect(route('home'));
});

it('requires login to download participant lists', function () {
    $this->get(route('activity.participants.download', $this->activity))->assertRedirect(route('login'));
});

it('gives assigned organizers the same Excel workbook as admins', function () {
    $this->activity->update(['organizer_user_ids' => [$this->organizer->id]]);
    $application = Application::factory()->create([
        'activity_id' => $this->activity->id,
        'user_id' => User::factory()->create()->id,
        'status' => ApplicationStatus::Active,
        'email' => 'participant@example.test',
        'comment' => 'INTERNAL_PAYMENT_NOTE',
    ]);
    $application->guests()->save(Guest::factory()->make(['email' => 'guest@example.test']));
    Payment::factory()->create([
        'application_id' => $application->id,
        'status' => PaymentStatus::paid,
        'price' => 98765.43,
        'mollieId' => 'tr_PRIVATE_PAYMENT',
    ]);
    Application::factory()->create([
        'activity_id' => $this->activity->id, 'user_id' => User::factory()->create()->id,
        'status' => ApplicationStatus::Reserve, 'email' => 'reserve@example.test',
    ]);
    Application::factory()->create([
        'activity_id' => $this->activity->id, 'user_id' => User::factory()->create()->id,
        'status' => ApplicationStatus::Pending, 'email' => 'pending-private@example.test',
    ]);
    Application::factory()->create([
        'activity_id' => $this->activity->id, 'user_id' => User::factory()->create()->id,
        'status' => ApplicationStatus::Cancelled, 'email' => 'cancelled-private@example.test',
    ]);

    $response = $this->actingAs($this->organizer)->get(route('activity.participants.download', $this->activity))
        ->assertOk()->assertDownload('aanmeldingen_' . $this->activity->id . '.xlsx');
    $path = $response->baseResponse->getFile()->getPathname();

    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        expect($spreadsheet->getSheetNames())->toBe(['Aanmeldingen', 'Reserves'])
            ->and($spreadsheet->getSheet(0)->getCell('G2')->getValue())->toBe('Betaalde bedrag')
            ->and((float) $spreadsheet->getSheet(0)->getCell('G3')->getValue())->toBe(98765.43);

        $cells = collect($spreadsheet->getAllSheets())->flatMap(fn ($sheet) => collect($sheet->toArray())->flatten())->all();
        expect($cells)->toContain('participant@example.test', 'guest@example.test', 'reserve@example.test')
            ->toContain('INTERNAL_PAYMENT_NOTE', 'Betaalde bedrag')
            ->not->toContain('pending-private@example.test', 'cancelled-private@example.test');
        $organizerSheets = array_map(fn ($sheet) => $sheet->toArray(), $spreadsheet->getAllSheets());
        $spreadsheet->disconnectWorksheets();
    } finally {
        unlink($path);
    }

    $adminResponse = $this->actingAs($this->admin)->get(route('admin.activities.download', $this->activity))->assertOk();
    $adminPath = $adminResponse->baseResponse->getFile()->getPathname();
    try {
        $adminSpreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($adminPath);
        expect(array_map(fn ($sheet) => $sheet->toArray(), $adminSpreadsheet->getAllSheets()))->toBe($organizerSheets);
        $adminSpreadsheet->disconnectWorksheets();
    } finally {
        unlink($adminPath);
    }
});

it('preserves the separate financial export for admins', function () {
    Application::factory()->create([
        'activity_id' => $this->activity->id,
        'user_id' => User::factory()->create()->id,
        'status' => ApplicationStatus::Active,
        'comment' => 'ADMIN_ONLY_NOTE',
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.activities.download', $this->activity))
        ->assertOk()->assertDownload('aanmeldingen_' . $this->activity->id . '.xlsx');
    $path = $response->baseResponse->getFile()->getPathname();

    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        expect($spreadsheet->getSheet(0)->getCell('G2')->getValue())->toBe('Betaalde bedrag')
            ->and($spreadsheet->getSheet(0)->getCell('F3')->getValue())->toBe('ADMIN_ONLY_NOTE');
        $spreadsheet->disconnectWorksheets();
    } finally {
        unlink($path);
    }
});

it('does not let organizers modify participants or manage the activity through direct requests', function () {
    $this->activity->update([
        'organizer_user_ids' => [$this->organizer->id],
        'cancellationEnd' => now()->addWeek(),
    ]);
    $application = Application::factory()->create([
        'activity_id' => $this->activity->id,
        'user_id' => User::factory()->create()->id,
        'status' => ApplicationStatus::Active,
    ]);

    $this->actingAs($this->organizer)
        ->delete(route('application.destroy', $application))->assertForbidden();
    $this->put(route('application.adminUpdateStatuses', $this->activity), [])->assertRedirect(route('home'));
    $this->post(route('application.adminStore', $this->activity), [])->assertRedirect(route('home'));
    $this->post(route('activity.notifyParticipants', $this->activity), [])->assertRedirect(route('home'));
    $this->delete(route('activity.destroy', $this->activity))->assertRedirect(route('home'));

    expect($application->fresh()->status)->toBe(ApplicationStatus::Active);
    Mail::assertNothingSent();
});

it('rejects duplicate or deleted organizer accounts', function (string $selection) {
    $ids = [$this->organizer->id, $this->organizer->id];
    if ($selection === 'deleted') {
        $this->organizer->update(['deleted_at' => now()->subDay()]);
        $ids = [$this->organizer->id];
    }

    $this->actingAs($this->admin)->put(route('activity.update', $this->activity), [
        'end-date' => $this->activity->end->toDateString(),
        'organizer_user_ids' => $ids,
    ])->assertSessionHasErrors('organizer_user_ids.0');

    expect($this->activity->fresh()->organizer_user_ids)->toBeNull();
})->with(['duplicate', 'deleted']);

it('stores selected organizer accounts when creating an activity', function () {
    \Illuminate\Support\Facades\Storage::fake('public');

    $this->actingAs($this->admin)->post(route('activity.store'), [
        'title' => 'Activiteit met medeorganisator',
        'description' => json_encode(['blocks' => []]),
        'image-upload' => \Illuminate\Http\UploadedFile::fake()->image('activity.png', 10, 10),
        'location' => 'Almere',
        'organizer' => $this->admin->name,
        'organizer_user_ids' => [$this->organizer->id],
        'maxParticipants' => 10,
        'maxGuests' => 2,
        'price' => '0.00',
        'whatsappUrl' => null,
        'free_organizer_count' => 0,
        'start-date' => now()->addWeek()->toDateString(),
        'start-time' => '12:00',
        'end-date' => now()->addWeek()->toDateString(),
        'end-time' => '16:00',
        'registrationStart' => now()->toDateString(),
        'registrationEnd' => now()->addDays(3)->toDateString(),
        'cancellationEnd' => now()->addDays(3)->toDateString(),
    ])->assertSessionHasNoErrors()->assertRedirect();

    expect(Activity::where('title', 'Activiteit met medeorganisator')->firstOrFail()->organizer_user_ids)
        ->toBe([$this->organizer->id]);
});