<?php

use App\Mail\NewActivity;
use App\Models\Activity;
use App\Models\Content;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates valid Power Automate JSON when the activity description has line breaks', function () {
    Content::create([
        'type' => 'email',
        'name' => 'email-nieuwe-activiteit',
        'title' => 'Nieuwe activiteit',
        'text' => 'Beste leden,',
    ]);

    $activity = Activity::factory()->create([
        'description' => "Eerste regel\n\nTweede regel",
    ]);

    $mail = new NewActivity($activity, collect(['lid@example.com']), [
        'batch_size' => 25,
        'delay' => 10,
    ]);

    $jsonBody = $mail->content()->with['jsonBody'];
    $payload = json_decode($jsonBody, true, 512, JSON_THROW_ON_ERROR);

    expect($jsonBody)
        ->not->toContain('<p>')
        ->not->toContain('[&>a]:')
        ->and($payload['body'])
        ->toContain('<p>Eerste regel')
        ->toContain('Tweede regel')
        ->and($payload['subject'])
        ->toBe('Nieuwe activiteit #Z');

    expect(prepareMailHtml('<p class="text-base [&>a]:underline">Bekijk <a href="https://example.com">de route</a></p>'))
        ->toBe('<p>Bekijk <a href="https://example.com">de route</a></p>');

    expect(prepareMailHtml('<p><a href="https://example.com">Je kan je hiervoor inschrijven.</a></p><h3>Startpunt</h3>'))
        ->toBe('<p><a href="https://example.com">Je kan je hiervoor inschrijven.</a></p><h3>Startpunt</h3>');

    expect(prepareMailDescriptionHtml('<p><a href="https://example.com">Iedere tekst blijft zichtbaar.</a></p><h3><a href="https://example.com">Startpunt</a></h3>'))
        ->toBe('<p>Iedere tekst blijft zichtbaar.</p><h3>Startpunt</h3>');

    expect(prepareMailDescriptionHtml('<p><a href="https://example.com">Deze foutieve link bevat te veel tekst en mag daarom niet de volledige rest van de activiteit als een grote link meenemen.</a></p>'))
        ->toBe('<p>Deze foutieve link bevat te veel tekst en mag daarom niet de volledige rest van de activiteit als een grote link meenemen.</p>');

    expect(view('mail.new-activity', [
        'activity' => $activity,
        'user' => null,
        'content' => Content::where('name', 'email-nieuwe-activiteit')->first(),
        'introHtml' => '',
        'description' => '<p><a href="https://example.com">Een normale link</a></p>',
    ])->render())
        ->toContain('tot en met')
        ->toMatch('/tot en met [^<]+<\/p>/')
        ->toContain('<strong>Wanneer:</strong>');
});