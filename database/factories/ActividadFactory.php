<?php

namespace Database\Factories;

use App\Enums\FrecuenciaEnum;
use App\Enums\TipoActividadEnum;
use App\Models\Actividad;
use App\Models\Infocentro;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Actividad>
 */
class ActividadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tipo' => fake()->randomElement(TipoActividadEnum::cases()),
            'activa' => true,
            'frecuencia' => fake()->randomElement(FrecuenciaEnum::cases()),
            'participantes' => fake()->numberBetween(5, 40),
            'detalle' => fake()->sentence(3),
            'opcion_si_no' => null,
            'opcion_nivel' => null,
            'infocentro_id' => Infocentro::factory(),
        ];
    }

    /**
     * Indicate that the activity is not currently offered.
     */
    public function inactiva(): static
    {
        return $this->state(fn (array $attributes): array => [
            'activa' => false,
            'frecuencia' => null,
            'participantes' => null,
            'detalle' => null,
            'opcion_si_no' => null,
            'opcion_nivel' => null,
        ]);
    }
}
