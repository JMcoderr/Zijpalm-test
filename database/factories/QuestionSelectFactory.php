<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuestionSelect>
 */
class QuestionSelectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Returns a random bool to check if the price should be set
        $price = (bool)rand(0,1);
        return [
            'question_id' => fake()->numberBetween(1, 10),
            'option' => fake()->word(),
            'price' => $price ? fake()->randomFloat(2, 0, 10) : null,
        ];
    }
}
