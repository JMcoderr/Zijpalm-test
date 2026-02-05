<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\UserType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'firstName' => 'Zijpalm',
            'lastName' => 'Bestuur',
            'email' => 'zijpalm@almere.nl',
            'password' => Hash::make(env('ADMIN_PASSWORD', 'admin')),
            'type' => UserType::System,
            'notifications' => 0,
        ]);

        $this->call([
            ContentSeeder::class,
            ProductSeeder::class,
        ]);

        // Only run this in local environment
        if (env('APP_ENV') === 'local') {
            // Create 100 random users
            User::factory(100)->create();
            Product::factory(10)->create();

            $this->call([
                ActivitySeeder::class,
                ApplicationSeeder::class,
                ReportSeeder::class,
            ]);
        }


    }
}
