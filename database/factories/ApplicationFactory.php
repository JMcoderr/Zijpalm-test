<?php

namespace Database\Factories;

use App\ApplicationStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Application>
 */
class ApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'activity_id' => $this->faker->unique()->numberBetween(1, 33),
            'user_id' => $this->faker->unique(true)->numberBetween(2, 101),
            'participants' => 1,
            'phone' => fake()->numerify('########'),
            'email' => fake()->unique()->safeEmail(),
            'comment' => fake()->optional()->sentence(),
            'status' => ApplicationStatus::Pending,
        ];
    }
}
