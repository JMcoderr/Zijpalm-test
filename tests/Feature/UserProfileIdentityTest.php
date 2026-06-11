<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('prevents regular users from updating their own name and email', function () {
    $user = User::factory()->create([
        'firstName' => 'Jordy',
        'lastName' => 'Meijer',
        'email' => 'jordy@example.com',
        'phone' => '12345678',
    ]);

    $this->actingAs($user)
        ->put(route('user.update', $user), [
            'firstName' => 'Changed',
            'lastName' => 'Name',
            'email' => 'changed@example.com',
            'phone' => '87654321',
        ])
        ->assertRedirect();

    $user->refresh();

    expect($user->firstName)->toBe('Jordy')
        ->and($user->lastName)->toBe('Meijer')
        ->and($user->email)->toBe('jordy@example.com')
        ->and($user->phone)->toBe('87654321');
});

it('allows admins to update user name and email', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create([
        'firstName' => 'Jordy',
        'lastName' => 'Meijer',
        'email' => 'jordy@example.com',
    ]);

    $this->actingAs($admin)
        ->put(route('user.update', $user), [
            'firstName' => 'Changed',
            'lastName' => 'Name',
            'email' => 'changed@example.com',
        ])
        ->assertRedirect();

    $user->refresh();

    expect($user->firstName)->toBe('Changed')
        ->and($user->lastName)->toBe('Name')
        ->and($user->email)->toBe('changed@example.com');
});
