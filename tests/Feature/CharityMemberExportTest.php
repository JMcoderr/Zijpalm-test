<?php

use App\Models\Content;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;

uses(RefreshDatabase::class);

it('keeps the member export off charity and lets admins download an Excel list', function () {
    foreach (['banner', 'info', 'bijdragen', 'deelnemers', 'contact'] as $block) {
        Content::forceCreate([
            'name' => 'lief-en-leed-' . $block, 'type' => 'text', 'title' => 'Lief en leed', 'text' => '',
        ]);
    }
    $this->get(route('information.charity'))->assertOk()->assertDontSee('Ledenlijst exporteren');
    $this->get(route('admin.users.export'))->assertRedirect(route('home'));
    $member = User::factory()->create(['firstName' => 'Exportlid', 'phone' => null]);
    $this->actingAs($member)->get(route('information.charity'))->assertOk()->assertDontSee('Ledenlijst exporteren');
    $this->get(route('admin.users.export'))->assertRedirect(route('home'));
    $this->actingAs(User::factory()->admin()->create());
    $this->get(route('information.charity'))->assertOk()->assertDontSee('Ledenlijst exporteren')
        ->assertDontSee(route('admin.users.export'));

    $response = $this->get(route('admin.users.export'))->assertOk();
    $path = $response->baseResponse->getFile()->getPathname();
    try {
        $sheet = IOFactory::load($path)->getActiveSheet();
        $rows = collect($sheet->toArray())->filter(fn ($row) => $row[0] === 'Exportlid');
        expect($response->headers->get('content-disposition'))->toContain('.xlsx')
            ->and($rows)->toHaveCount(1)->and($rows->first()[3])->toBeNull();
    } finally {
        unlink($path);
    }
});