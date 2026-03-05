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
        // Remove existing test users to prevent duplicates
        User::withTrashed()->where('email', 'jmeijer04@almere.nl')->forceDelete();
        User::withTrashed()->where('email', 'jmeijer@almere.nl')->forceDelete();
        User::withTrashed()->where('email', 'yasminsabae@gmail.com')->forceDelete();

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
        
        // Admin user Yasmin Sabe
        User::create([
            'firstName' => 'Yasmin',
            'lastName' => 'Sabe',
            'email' => 'yasminsabae@gmail.com',
            'password' => Hash::make('yasmin23'),
            'phone' => '0610203040',
            'type' => UserType::Medewerker,
            'is_admin' => true,
            'email_verified_at' => now(),
            'notifications' => 0,
         ]); 
    }
}
