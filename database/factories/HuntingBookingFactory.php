<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HuntingBooking>
 */
class HuntingBookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tourNames = [
            'Deer Hunting Expedition',
            'Wild Boar Hunt',
            'Duck Hunting Tour',
            'Elk Mountain Hunt',
            'Bear Tracking Adventure',
        ];

        return [
            'tour_name' => fake()->randomElement($tourNames),
            'hunter_name' => fake()->name(),
            'guide_id' => \App\Models\Guide::factory(),
            'date' => fake()->dateTimeBetween('now', '+3 months'),
            'participants_count' => fake()->numberBetween(1, 10),
        ];
    }
}
