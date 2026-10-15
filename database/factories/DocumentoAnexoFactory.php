<?php

namespace Database\Factories;

use App\Enums\DocumentoAnexoEnum;
use App\Models\DocumentoAnexo;
use App\Models\Infocentro;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentoAnexo>
 */
class DocumentoAnexoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tipo = fake()->randomElement(DocumentoAnexoEnum::cases());

        return [
            'tipo' => $tipo,
            'etiqueta' => $tipo === DocumentoAnexoEnum::Otros ? fake()->words(3, true) : null,
            'ruta' => 'diagnostico/1/'.$tipo->value.'.pdf',
            'nombre_original' => fake()->word().'.pdf',
            'mime' => 'application/pdf',
            'tamano' => fake()->numberBetween(50_000, 2_000_000),
            'infocentro_id' => Infocentro::factory(),
        ];
    }
}
