<?php

namespace Database\Factories;

use App\Models\WaitlistEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WaitlistEntry>
 */
class WaitlistEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'nombre' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'telefono' => null,
            'fecha' => $this->faker->dateTimeBetween('now', '+21 days')->format('Y-m-d'),
            'hora' => $this->faker->randomElement(['13:00', '13:30', '14:00', '20:00', '20:30', '21:00']),
            'personas' => $this->faker->numberBetween(1, 6),
            'estado' => 'esperando',
        ];
    }
}
