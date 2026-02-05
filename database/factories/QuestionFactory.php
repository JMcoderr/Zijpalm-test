<?php

namespace Database\Factories;

use App\QuestionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(QuestionType::toArray());
        // Returns a random bool to check if the price should be set
        $price = (bool)rand(0,1);
        return [
            'activity_id' => fake()->numberBetween(1, 10),
            'query' => fake()->sentence(),
            'type' => $type,
            'price' => $type != QuestionType::Select->value && $type != QuestionType::Text->value && $price ? fake()->randomFloat(2, 0, 100) : null,
            'max_amount' => $type == QuestionType::Number->value ? fake()->numberBetween(1, 10) : null,
        ];
    }
}
