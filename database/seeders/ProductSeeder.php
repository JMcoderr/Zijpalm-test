<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::create([
            'name' => 'Contributie',
            'description' => 'Standaard artikel voor de contributie.',
            'price' => 2.00,
            'hidden' => true,
        ]);
    }
}
