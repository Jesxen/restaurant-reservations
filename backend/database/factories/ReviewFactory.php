<?php

namespace Database\Factories;

use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'reserva_id' => null,
            'nombre' => $this->faker->name(),
            'rating' => $this->faker->numberBetween(1, 5),
            'comentario' => $this->faker->sentence(12),
            'aprobada' => false,
        ];
    }

    public function aprobada(): static
    {
        return $this->state(fn (array $attributes) => ['aprobada' => true]);
    }
}
