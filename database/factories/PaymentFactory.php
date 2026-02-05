<?php

namespace Database\Factories;

use App\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mollieId' => $this->faker->uuid(),
            'description' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(PaymentStatus::toArray()),
            'price' => $this->faker->randomFloat(2, 0, 100),
            'paidAt' => $this->faker->dateTime(),
        ];
    }
}
