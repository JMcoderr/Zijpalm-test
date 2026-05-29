<?php

use App\Imports\MembersImport;
use App\Imports\UsersImport;
use App\Models\User;
use App\UserType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

uses(RefreshDatabase::class);

it('copies phone numbers when importing members', function () {
    $import = new MembersImport();

    $import->collection(new Collection([
        [
            'mailadres' => 'lid@example.com',
            'voornaam' => 'Jan',
            'achternaam' => 'Jansen',
            'functie' => 'gepensioneerde',
            'telefoon' => '+31 6 1234 5678',
        ],
    ]));

    $user = User::where('email', 'lid@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->phone)->toBe('12345678');
    expect($user->type)->toBe(UserType::Gepensioneerde);
});

it('copies phone numbers when importing employees', function () {
    $import = new UsersImport();

    $import->collection(new Collection([
        [
            'persnr' => '12345',
            'e_mail' => 'medewerker@example.com',
            'omschrijving' => 'Zijpalm',
            'naam_medewerkster' => 'Jansen, B. de (Bert)',
            'telefoon' => '06-12345678',
        ],
    ]));

    $user = User::where('email', 'medewerker@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->phone)->toBe('12345678');
    expect($user->type)->toBe(UserType::Medewerker);
});