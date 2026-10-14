<?php

namespace Database\Factories;

use App\Models\Infocentro;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Infocentro>
 */
class InfocentroFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->company(),
            'codigo' => strtoupper(fake()->unique()->bothify('INF-###')),
            'direccion' => fake()->address(),
            'telefono' => fake()->numerify('########'),
            'activo' => true,
        ];
    }

    /**
     * Indicate that the infocentro is deactivated.
     */
    public function inactivo(): static
    {
        return $this->state(fn (array $attributes): array => [
            'activo' => false,
        ]);
    }
}
