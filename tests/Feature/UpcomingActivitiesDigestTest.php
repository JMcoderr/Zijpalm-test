<?php

use App\ActivityType;
use App\Mail\UpcomingActivitiesDigest;
use App\Models\Activity;
use App\Models\Content;
use App\Models\Product;
use App\Models\User;
use App\UserNotifications;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('preserves the digest automation contract and plain text activity links', function () {
    Content::updateOrCreate(['name' => 'email-toekomstige-activiteiten'], [
        'type' => 'email',
        'title' => 'Komende activiteiten', 'text' => 'Nieuws van Zijpalm',
    ]);
    $activity = Activity::factory()->create([
        'title' => 'Workshop in de toekomst', 'type' => ActivityType::OneDay,
        'start' => now()->addDays(10), 'end' => now()->addDays(10)->addHours(2),
    ]);
    $running = Activity::factory()->create([
        'title' => 'Doorlopende cursus fotografie', 'type' => ActivityType::MultiDay,
        'start' => now()->subDay(), 'end' => now()->addDay(),
    ]);
    $mail = new UpcomingActivitiesDigest(collect(['member@example.com']), collect([$activity, $running]), collect([$running]), [
        'batch_size' => '50', 'delay' => '61',
    ]);

    $content = $mail->content();
    $payload = json_decode($content->with['jsonBody'], true, flags: JSON_THROW_ON_ERROR);

    expect($mail->envelope()->subject)->toBe('AUTOMATE BATCH upcoming_activities_digest')
        ->and($content->text)->toBe('mail.raw-json')
        ->and(array_keys($payload))->toBe(['emails', 'subject', 'body', 'batch_size', 'delay'])
        ->and($payload['emails'])->toBe(['member@example.com'])
        ->and($payload['subject'])->toBe('Zijpalm | Komende activiteiten')
        ->and($payload['batch_size'])->toBe(50)
        ->and($payload['delay'])->toBe(61)
        ->and($payload['body'])->toContain('Nieuws van Zijpalm', $activity->title, $running->title)
        ->toContain('zijpalm.nl/activiteiten/' . $activity->id)
        ->not->toContain('href="https://zijpalm.nl/activiteiten/')
        ->and(substr_count($payload['body'], $running->title))->toBe(1)
        ->and($mail->attachments())->toBe([]);
});

it('sends only selected shop products without including unselected activities', function () {
    Mail::fake();
    $recipient = User::factory()->create(['notifications' => UserNotifications::NEWSLETTER->value]);
    $product = Product::factory()->create(['name' => 'Geselecteerde handdoek', 'hidden' => false]);
    Product::factory()->create(['name' => 'Niet geselecteerd product', 'hidden' => false]);
    Activity::factory()->create([
        'type' => ActivityType::OneDay,
        'start' => now()->addDays(10), 'end' => now()->addDays(11),
    ]);

    $this->artisan('app:send-upcoming-activities-digest', [
        '--batch_size' => 50, '--delay' => 61, '--product_ids' => [$product->id],
    ])->assertExitCode(0);

    Mail::assertSent(UpcomingActivitiesDigest::class, function ($mail) use ($product, $recipient) {
        $payload = json_decode($mail->content()->with['jsonBody'], true, flags: JSON_THROW_ON_ERROR);
        expect($mail->activities)->toHaveCount(0)
            ->and($mail->runningActivities)->toHaveCount(0)
            ->and($mail->products->pluck('id')->all())->toBe([$product->id])
            ->and($payload['emails'])->toBe([$recipient->email])
            ->and($payload['body'])->toContain('Shop / Overig', $product->name, 'zijpalm.nl/shop/' . $product->id)
            ->not->toContain('Niet geselecteerd product')
            ->and($payload['batch_size'])->toBe(50)
            ->and($payload['delay'])->toBe(61);

        return $mail->hasTo(config('mail.bestuur.address'));
    });
    Mail::assertSentCount(1);
});

it('rejects an empty explicit selection instead of mailing every item', function () {
    Mail::fake();
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('activity.sendUpcomingActivitiesDigest'), [
            'batch_size' => 50, 'delay' => 61, 'selection_submitted' => 1,
        ])
        ->assertSessionHasErrors('activity_ids');

    Mail::assertNothingSent();
});

it('lists visible shop items once and excludes hidden expired and cancelled items', function () {
    $activity = Activity::factory()->create([
        'title' => 'Normale activiteit', 'type' => ActivityType::OneDay,
        'start' => now()->addDays(10), 'end' => now()->addDays(11),
    ]);
    $linked = Activity::factory()->create([
        'title' => 'Dubbele activiteitnaam', 'type' => ActivityType::OneDay,
        'start' => now()->addDays(10), 'end' => now()->addDays(11),
    ]);
    Product::factory()->create(['name' => 'Zichtbare handdoek', 'hidden' => false]);
    Product::factory()->create([
        'name' => 'Shop excursie', 'hidden' => false, 'product_type' => 'activity',
        'activity_id' => $linked->id, 'activity_starts_at' => now()->addDays(10),
    ]);
    Product::factory()->create(['name' => 'Verborgen product', 'hidden' => true]);
    Product::factory()->create([
        'name' => 'Afgelopen excursie', 'hidden' => false, 'product_type' => 'activity',
        'activity_starts_at' => now()->subDays(3), 'activity_ends_at' => now()->subDay(),
    ]);
    Product::factory()->create([
        'name' => 'Oude excursie zonder einddatum', 'hidden' => false, 'product_type' => 'activity',
        'activity_starts_at' => now()->subDays(3),
    ]);
    Product::factory()->create([
        'name' => 'Geannuleerde excursie', 'hidden' => false, 'product_type' => 'activity',
        'activity_id' => Activity::factory()->create(['type' => ActivityType::Cancelled])->id,
    ]);

    \Livewire\Livewire::actingAs(User::factory()->admin()->create())
        ->test(\App\Livewire\UpcomingActivitiesDigestMail::class)
        ->assertSee($activity->title)->assertSee('Zichtbare handdoek')->assertSee('Shop excursie')
        ->assertSee('data-digest-selection', false, false)
        ->assertSee('data-digest-group="activities"', false, false)
        ->assertSee('data-digest-group="products"', false, false)
        ->assertSee('md:grid-cols-2', false, false)
        ->assertSee('name="activity_ids[]"', false, false)
        ->assertSee('name="product_ids[]"', false, false)
        ->assertDontSee($linked->title)->assertDontSee('Verborgen product')
        ->assertDontSee('Afgelopen excursie')->assertDontSee('Oude excursie zonder einddatum')
        ->assertDontSee('Geannuleerde excursie');
});

it('sends a mixed selection through the existing admin route to newsletter recipients only', function () {
    Mail::fake();
    $admin = User::factory()->admin()->create(['notifications' => 0]);
    $recipient = User::factory()->create(['notifications' => UserNotifications::NEWSLETTER->value]);
    User::factory()->create(['notifications' => 0]);
    User::factory()->create(['notifications' => UserNotifications::NEWSLETTER->value])->delete();
    $activity = Activity::factory()->create([
        'title' => 'Geselecteerde workshop', 'type' => ActivityType::OneDay,
        'start' => now()->addDays(10), 'end' => now()->addDays(11),
    ]);
    $product = Product::factory()->create(['name' => 'Geselecteerd shopartikel', 'hidden' => false]);
    Product::factory()->create(['name' => 'Overgeslagen shopartikel', 'hidden' => false]);

    $this->actingAs($admin)->post(route('activity.sendUpcomingActivitiesDigest'), [
        'batch_size' => 50, 'delay' => 61, 'selection_submitted' => 1,
        'activity_ids' => [$activity->id], 'product_ids' => [$product->id],
    ])->assertSessionHasNoErrors()->assertRedirect(route('activity.index'));

    Mail::assertSent(UpcomingActivitiesDigest::class, function ($mail) use ($activity, $product, $recipient) {
        $payload = json_decode($mail->content()->with['jsonBody'], true, flags: JSON_THROW_ON_ERROR);
        expect($mail->activities->pluck('id')->all())->toBe([$activity->id])
            ->and($mail->products->pluck('id')->all())->toBe([$product->id])
            ->and($payload['emails'])->toBe([$recipient->email])
            ->and($payload['body'])->toContain($activity->title, $product->name, 'role="presentation"')
            ->not->toContain('Overgeslagen shopartikel');

        return $mail->hasTo(config('mail.bestuur.address'));
    });
    Mail::assertSentCount(1);
});

it('does not send hidden or expired products even when their ids are submitted', function () {
    Mail::fake();
    User::factory()->create(['notifications' => UserNotifications::NEWSLETTER->value]);
    $hidden = Product::factory()->create(['hidden' => true]);
    $expired = Product::factory()->create([
        'hidden' => false, 'product_type' => 'activity',
        'activity_starts_at' => now()->subDays(2), 'activity_ends_at' => now()->subDay(),
    ]);

    $this->artisan('app:send-upcoming-activities-digest', [
        '--batch_size' => 50, '--delay' => 61, '--product_ids' => [$hidden->id, $expired->id],
    ])->assertExitCode(0);

    Mail::assertNothingSent();
});

it('keeps shop products in the fallback body if rendering fails', function () {
    $product = Product::factory()->create(['name' => 'Fallback handdoek']);
    view()->composer('mail.upcoming-activities-digest', function () {
        throw new RuntimeException('Deliberate render failure in test');
    });
    $mail = new UpcomingActivitiesDigest(collect(['member@example.com']), collect(), collect(), [
        'batch_size' => 50, 'delay' => 61,
    ], collect([$product]));

    $payload = json_decode($mail->content()->with['jsonBody'], true, flags: JSON_THROW_ON_ERROR);
    expect($payload['body'])->toContain($product->name, 'zijpalm.nl/shop/' . $product->id)
        ->and($payload['batch_size'])->toBe(50)->and($payload['delay'])->toBe(61);
});

it('builds a real plain text JSON email without contacting SMTP', function () {
    $mail = new UpcomingActivitiesDigest(collect(['member@example.com']), collect(), collect(), [
        'batch_size' => 50, 'delay' => 61,
    ], collect([Product::factory()->create(['name' => 'Veilig mailvoorbeeld'])]));

    $sent = Mail::mailer('array')->to('board@example.com')->send($mail);
    $message = $sent->getOriginalMessage();
    $payload = json_decode($message->getTextBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($message->getSubject())->toBe('AUTOMATE BATCH upcoming_activities_digest')
        ->and($payload['body'])->toContain('Veilig mailvoorbeeld', 'Shop / Overig')
        ->and(array_keys($payload))->toBe(['emails', 'subject', 'body', 'batch_size', 'delay'])
        ->and($payload['batch_size'])->toBe(50)->and($payload['delay'])->toBe(61);
});

it('keeps linked shop activities out of the activity sections', function (bool $activitiesOnly) {
    Mail::fake();
    User::factory()->create(['notifications' => UserNotifications::NEWSLETTER->value]);
    $activity = Activity::factory()->create([
        'title' => 'Reguliere nieuwsbriefactiviteit', 'type' => ActivityType::OneDay,
        'start' => now()->addDays(10), 'end' => now()->addDays(11),
    ]);
    $linked = Activity::factory()->create([
        'title' => 'Geen dubbele vermelding', 'type' => ActivityType::OneDay,
        'start' => now()->addDays(10), 'end' => now()->addDays(11),
    ]);
    $product = Product::factory()->create([
        'name' => 'Shopactiviteit in nieuwsbrief', 'hidden' => false,
        'product_type' => 'activity', 'activity_id' => $linked->id,
        'activity_starts_at' => now()->addDays(10),
    ]);
    $options = ['--batch_size' => 50, '--delay' => 61];
    if ($activitiesOnly) {
        $options['--activity_ids'] = [$activity->id];
    }

    $this->artisan('app:send-upcoming-activities-digest', $options)->assertExitCode(0);

    Mail::assertSent(UpcomingActivitiesDigest::class, function ($mail) use ($activity, $product, $activitiesOnly) {
        $payload = json_decode($mail->content()->with['jsonBody'], true, flags: JSON_THROW_ON_ERROR);
        expect($mail->activities->pluck('id')->all())->toBe([$activity->id])
            ->and($mail->products->pluck('id')->all())->toBe($activitiesOnly ? [] : [$product->id])
            ->and($payload['body'])->toContain($activity->title)
            ->not->toContain('Geen dubbele vermelding');

        return true;
    });
    Mail::assertSentCount(1);
})->with(['default selection' => false, 'activities only' => true]);

it('validates digest input before sending mail', function (array $invalid, string $field) {
    Mail::fake();
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('activity.sendUpcomingActivitiesDigest'), array_replace([
            'batch_size' => 50, 'delay' => 61, 'selection_submitted' => 1,
        ], $invalid))
        ->assertSessionHasErrors($field);

    Mail::assertNothingSent();
})->with([
    'unknown product' => [['product_ids' => [999999]], 'product_ids.0'],
    'invalid product id' => [['product_ids' => ['invalid']], 'product_ids.0'],
    'invalid batch size' => [['batch_size' => 0], 'batch_size'],
    'invalid delay' => [['delay' => -1], 'delay'],
]);

it('does not allow regular members to send the digest', function () {
    Mail::fake();
    $this->actingAs(User::factory()->create(['is_admin' => false]))
        ->post(route('activity.sendUpcomingActivitiesDigest'), [
            'batch_size' => 50, 'delay' => 61,
        ])->assertRedirect(route('home'));

    Mail::assertNothingSent();
});