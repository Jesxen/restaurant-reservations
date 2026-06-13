<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reserva>
 */
class ReservaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nombre = $this->faker->name();

        return [
            'user_id' => null,
            'mesa_id' => null,
            'nombre' => $nombre,
            'email' => $this->faker->safeEmail(),
            'fecha' => $this->faker->dateTimeBetween('now', '+21 days')->format('Y-m-d'),
            'hora' => $this->faker->randomElement(['13:00', '13:30', '14:00', '20:00', '20:30', '21:00', '21:30']),
            'personas' => $this->faker->numberBetween(1, 6),
            'estado' => $this->faker->randomElement(['pendiente', 'confirmada', 'confirmada', 'completada', 'cancelada']),
            'notas' => null,
        ];
    }
}
