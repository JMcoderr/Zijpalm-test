<?php

use App\Models\Content;

it('shows informal wording in the reset password mail', function () {
    $content = new Content([
        'text' => '{"time":1769171792368,"blocks":[{"id":"xnNwYZd4RN","type":"paragraph","data":{"text":"U ontvangt deze email omdat we een wachtwoord reset aanvraag hebben ontvangen voor uw account."}}],"version":"2.31.0-rc.7"}',
    ]);

    $html = view('mail.reset-password', [
        'content' => $content,
        'resetUrl' => 'https://example.com/reset',
        'expire' => 60,
    ])->render();

    expect($html)
        ->toContain('Je ontvangt deze e-mail omdat we een wachtwoordresetaanvraag voor je account hebben ontvangen.')
        ->toContain('Als je geen wachtwoord hebt aangevraagd, hoef je verder niets te doen.')
        ->toContain('in je webbrowser')
        ->not->toContain('U ontvangt deze email omdat we een wachtwoord reset aanvraag hebben ontvangen voor uw account.')
        ->not->toContain('Als u geen wachtwoord heeft aangevraagd hoedt u verder niks te doen.')
        ->not->toContain('in uw web browser');
});