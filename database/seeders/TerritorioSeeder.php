<?php

namespace Database\Seeders;

use App\Models\Estado;
use App\Models\Municipio;
use Illuminate\Database\Seeder;
use RuntimeException;

class TerritorioSeeder extends Seeder
{
    /**
     * Seed the estados, municipios and parroquias of Venezuela.
     *
     * The catalogue lives in database/data/venezuela.json so seeding stays
     * reproducible and works without network access.
     */
    public function run(): void
    {
        $ruta = database_path('data/venezuela.json');

        if (! is_file($ruta)) {
            throw new RuntimeException("No se encontró el catálogo territorial en {$ruta}.");
        }

        $datos = json_decode(file_get_contents($ruta), true, flags: JSON_THROW_ON_ERROR);

        foreach ($datos as $estado) {
            $modelEstado = Estado::firstOrCreate(['nombre' => $estado['nombre']]);

            foreach ($estado['municipios'] as $municipio) {
                $modelMunicipio = Municipio::firstOrCreate([
                    'estado_id' => $modelEstado->id,
                    'nombre' => $municipio['nombre'],
                ]);

                $existentes = $modelMunicipio->parroquias()->pluck('nombre');

                $nuevas = array_values(array_diff($municipio['parroquias'], $existentes->all()));

                if ($nuevas !== []) {
                    $modelMunicipio->parroquias()->createMany(
                        array_map(fn (string $nombre): array => ['nombre' => $nombre], $nuevas)
                    );
                }
            }
        }
    }
}
