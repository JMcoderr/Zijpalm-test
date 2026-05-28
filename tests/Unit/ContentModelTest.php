<?php

use App\Models\Content;

it('returns an empty string for empty content text', function () {
    $content = new Content([
        'text' => '',
    ]);

    expect($content->textHTML)->toBe('');
});

it('wraps plain text in a paragraph', function () {
    $content = new Content([
        'text' => 'Hallo wereld',
    ]);

    expect($content->textHTML)->toBe('<p>Hallo wereld</p>');
});

it('renders editor json content to html', function () {
    $content = new Content([
        'text' => '{"time":1750075440441,"blocks":[{"type":"paragraph","data":{"text":"De details vindt je hieronder."}}],"version":"2.31.0-rc.7"}',
    ]);

    expect($content->textHTML)
        ->toContain('De details vindt je hieronder.')
        ->toContain('<p class=');
});