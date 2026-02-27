<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\UserType;

class JordyMeijerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin user
        User::create([
            'firstName' => 'Jordy',
            'lastName' => 'Meijer',
            'email' => 'jmeijer04@almere.nl',
            'password' => Hash::make('jordy21'),
            'phone' => '0610609212',
            'type' => UserType::Medewerker,
            'is_admin' => true,
            'email_verified_at' => now(),
            'notifications' => 0,
        ]);

        // Regular user
        User::create([
            'firstName' => 'Jordy',
            'lastName' => 'Meijer',
            'email' => 'jmeijer@almere.nl',
            'password' => Hash::make('jordy21'),
            'phone' => '0610609212',
            'type' => UserType::Medewerker,
            'is_admin' => false,
            'email_verified_at' => now(),
            'notifications' => 0,
        ]);
    }
}
