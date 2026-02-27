<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use BumpCore\EditorPhp\EditorPhp;
use App\ActivityType;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 month', '+1 month');
        $end = fake()->dateTimeBetween($start, (clone $start)->modify('+72 hours'));
        $registrationStart = fake()->dateTimeInInterval($start, '-1 month');
        $registrationEnd = fake()->dateTimeBetween($registrationStart, $start);
        $cancellationEnd = fake()->dateTimeBetween($registrationEnd, $start);

        // If start and end date is the same
        if($start->format('Y-m-d') === $end->format('Y-m-d')){
            $type = ActivityType::OneDay;
        }

        // If start and end date aren't the same
        if($start->format('Y-m-d') !== $end->format('Y-m-d')){
            $type = ActivityType::MultiDay;
        }

        // If the now is greater than the end
        if(now() >= $end){
            $type = ActivityType::Archived;
        }

        // Only cancel 10% of activities instead of 50%
        if(fake()->boolean(10)){
            $type = ActivityType::Cancelled;
        }

        return [
            'type' => $type,
            'title' => fake()->sentence(4),
            'description' => EditorPhp::fake(false, 1, 5),
            'start' => $start,
            'end' => $end,
            'registrationStart' => $registrationStart,
            'registrationEnd' => $registrationEnd,
            'cancellationEnd' => $cancellationEnd,
            'location' => fake()->city(),
            'organizer' => fake()->name(),
            'imagePath' => 'images/hans.webp', // Moet in storage/app/public/images/hans.webp staan
            'price' => fake()->randomFloat(2, 0, 100),
            'maxParticipants' => fake()->boolean(50) ? null : fake()->numberBetween(1, 100),
            'maxGuests' => fake()->numberBetween(0, 3),
        ];
    }
}
