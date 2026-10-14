<?php

namespace Database\Factories;

use App\Enums\EstadoEquipoEnum;
use App\Enums\TipoEnergiaEnum;
use App\Models\Energia;
use App\Models\Infocentro;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Energia>
 */
class EnergiaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tipo' => fake()->randomElement(TipoEnergiaEnum::cases()),
            'cantidad' => fake()->numberBetween(1, 6),
            'estado' => fake()->randomElement([
                EstadoEquipoEnum::Optimo,
                EstadoEquipoEnum::Bueno,
                EstadoEquipoEnum::Regular,
            ]),
            'detalle' => null,
            'observaciones' => null,
            'infocentro_id' => Infocentro::factory(),
        ];
    }
}
