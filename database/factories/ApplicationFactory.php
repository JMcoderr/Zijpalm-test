<?php

namespace Database\Factories;

use App\ApplicationStatus;
use App\Models\Activity;
use App\Models\User;
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
        $activityId = Activity::query()->inRandomOrder()->value('id');
        $userId = User::query()->where('id', '!=', 1)->inRandomOrder()->value('id')
            ?? User::query()->inRandomOrder()->value('id');

        return [
            'activity_id' => $activityId,
            'user_id' => $userId,
            'participants' => 1,
            'phone' => fake()->numerify('########'),
            'email' => fake()->unique()->safeEmail(),
            'comment' => fake()->optional()->sentence(),
            'status' => ApplicationStatus::Pending,
        ];
    }
}
