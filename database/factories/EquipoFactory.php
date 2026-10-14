<?php

namespace Database\Factories;

use App\Enums\EstadoEquipoEnum;
use App\Enums\TipoEquipoEnum;
use App\Models\Equipo;
use App\Models\Infocentro;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Equipo>
 */
class EquipoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cantidad = fake()->numberBetween(1, 12);

        return [
            'tipo' => fake()->randomElement(TipoEquipoEnum::cases()),
            'cantidad' => $cantidad,
            'funcionando' => null,
            'fuera_servicio' => null,
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
