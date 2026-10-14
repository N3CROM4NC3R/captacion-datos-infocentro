<?php

namespace Database\Factories;

use App\Enums\NivelAcademicoEnum;
use App\Models\Facilitador;
use App\Models\Infocentro;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Facilitador>
 */
class FacilitadorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->name(),
            'nivel_academico' => fake()->randomElement(NivelAcademicoEnum::cases()),
            'titulo_carrera' => fake()->randomElement(['Ingeniería en Computación', 'Técnico en Sólo Electrónica', 'Licenciatura en Educación']),
            'anios_experiencia' => fake()->numberBetween(1, 20),
            'formacion_tic' => true,
            'formacion_tic_detalle' => 'Curso de fundamentals TIC',
            'formacion_ciencias' => false,
            'formacion_ciencias_area' => null,
            'horas_semanales' => fake()->numberBetween(4, 40),
            'infocentro_id' => Infocentro::factory(),
        ];
    }

    /**
     * Indicate that the facilitator has sciences training.
     */
    public function conFormacionCiencias(): static
    {
        return $this->state(fn (array $attributes): array => [
            'formacion_ciencias' => true,
            'formacion_ciencias_area' => fake()->randomElement(['Matemáticas', 'Biología', 'Física', 'Química']),
        ]);
    }
}
