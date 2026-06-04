<?php

use App\ApplicationStatus;
use App\Mail\ActivityApplied;
use App\Mail\PaymentFailed;
use App\Models\Activity;
use App\Models\Application;
use App\Models\Content;
use App\Models\Payment;
use App\Models\User;
use App\PaymentStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

// Unit: checks if a price is formatted correctly.
it('checks the price helper', function (float $input, string $expected) {
    expect(formatPrice($input))->toBe($expected);
})->with([
    [0.0, '€-'],
    [12.5, '€12,50'],
]);


// Unit: checks if activity text becomes html.
it('checks the activity html helper', function (string $input, string $expected) {
    $activity = new Activity([
        'description' => $input,
    ]);

    expect($activity->descriptionHTML)->toBe($expected);
})->with([
    ['Hallo wereld', '<p>Hallo wereld</p>'],
    ['<b>Vet</b>', '<p>&lt;b&gt;Vet&lt;/b&gt;</p>'],
]);


// Unit: checks if validated batch values are cast to integers.
it('checks the validation helper', function (array $data, array $expected) {
    expect(castValidatedInts($data, ['batch_size', 'delay']))->toBe($expected);
})->with([
    [
        ['batch_size' => '25', 'delay' => '10'],
        ['batch_size' => 25, 'delay' => 10],
    ],
    [
        ['batch_size' => '0', 'delay' => '5'],
        ['batch_size' => 0, 'delay' => 5],
    ],
]);


// Unit: checks if payment status changes go to the right state.
it('checks the payment status helper', function (string $paymentState, ApplicationStatus $startStatus, ApplicationStatus $expectedStatus, ?string $expectedMail) {
    Content::firstOrCreate(['name' => 'email-activiteit-aangemeld'], [
        'type' => 'mail',
        'title' => 'Activity applied',
        'text' => 'Test content',
    ]);

    Content::firstOrCreate(['name' => 'email-activiteit-aangemeld-reserve'], [
        'type' => 'mail',
        'title' => 'Reserve content',
        'text' => 'Test content',
    ]);

    Content::firstOrCreate(['name' => 'email-betaling-mislukt'], [
        'type' => 'mail',
        'title' => 'Payment failed',
        'text' => 'Test content',
    ]);

    Mail::fake();

    $activity = \Mockery::mock(Activity::class);
    $activity->shouldReceive('updateApplications')->once();

    $user = new User();
    $user->id = 1;
    $user->name = 'Test User';
    $user->email = 'test@example.com';

    $application = \Mockery::mock(Application::class)->makePartial();
    $application->shouldReceive('save')->once()->andReturnTrue();
    $application->status = $startStatus;
    $application->setRelation('activity', $activity);
    $application->setRelation('user', $user);

    $payment = new Payment([
        'status' => PaymentStatus::from($paymentState),
    ]);

    $application->handleNewStatus($payment);

    expect($application->status)->toBe($expectedStatus);

    if ($expectedMail === ActivityApplied::class) {
        Mail::assertSent(ActivityApplied::class);
        return;
    }

    if ($expectedMail === PaymentFailed::class) {
        Mail::assertSent(PaymentFailed::class);
        return;
    }

    Mail::assertNothingSent();
})->with([
    ['paid', ApplicationStatus::Pending, ApplicationStatus::Active, ActivityApplied::class],
    ['failed', ApplicationStatus::Pending, ApplicationStatus::Cancelled, PaymentFailed::class],
]);