<?php

namespace Database\Factories;

use App\Enums\EstadoProyectoMincytEnum;
use App\Models\Infocentro;
use App\Models\VinculacionMincyt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VinculacionMincyt>
 */
class VinculacionMincytFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'institucion' => fake()->company().' '.fake()->randomElement(['Instituto', 'Fundación', 'Centro']),
            'proyecto' => fake()->sentence(4),
            'estado' => fake()->randomElement(EstadoProyectoMincytEnum::cases()),
            'ultimo_contacto' => fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'infocentro_id' => Infocentro::factory(),
        ];
    }

    /**
     * Indicate that the project has already concluded.
     */
    public function concluido(): static
    {
        return $this->state(fn (array $attributes): array => [
            'estado' => EstadoProyectoMincytEnum::Concluido,
        ]);
    }
}
