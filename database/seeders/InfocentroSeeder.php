<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\Estado;
use App\Models\Infocentro;
use App\Models\Municipio;
use App\Models\Parroquia;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class InfocentroSeeder extends Seeder
{
    /**
     * Seed example infocentros along with their user accounts.
     */
    public function run(): void
    {
        $infocentros = [
            [
                'nombre' => 'Infocentro Centro',
                'codigo' => 'INF-001',
                'direccion' => 'Av. Siempre Viva 742',
                'telefono' => '5551001',
                'estado' => 'Distrito Capital',
                'municipio' => 'Libertador',
                'parroquia' => 'Catedral',
            ],
            [
                'nombre' => 'Infocentro Norte',
                'codigo' => 'INF-002',
                'direccion' => 'Calle Prat 1234',
                'telefono' => '5551002',
                'estado' => 'Miranda',
                'municipio' => 'Guaicaipuro',
                'parroquia' => 'Los Teques',
            ],
        ];

        foreach ($infocentros as $indice => $datos) {
            $municipio = $this->municipio($datos['estado'], $datos['municipio']);

            $infocentro = Infocentro::firstOrCreate(
                ['codigo' => $datos['codigo']],
                [
                    'nombre' => $datos['nombre'],
                    'direccion' => $datos['direccion'],
                    'telefono' => $datos['telefono'],
                    'estado_id' => $municipio?->estado_id,
                    'municipio_id' => $municipio?->id,
                    'parroquia_id' => Parroquia::where('municipio_id', $municipio?->id)
                        ->where('nombre', $datos['parroquia'])
                        ->value('id'),
                    'activo' => true,
                ],
            );

            $user = $infocentro->user()->firstOrCreate(
                ['username' => 'infocentro'.($indice + 1)],
                [
                    'name' => 'Usuario '.($indice + 1),
                    'email' => 'infocentro'.($indice + 1).'@infocentro.test',
                    'password' => 'password',
                    'email_verified_at' => now(),
                ],
            );

            $user->assignRole(Role::findOrCreate(RoleEnum::Infocentro));
        }
    }

    /**
     * Resolve a seeded municipio by its estado and name.
     */
    private function municipio(string $estado, string $municipio): ?Municipio
    {
        $estadoId = Estado::where('nombre', $estado)->value('id');

        if ($estadoId === null) {
            return null;
        }

        return Municipio::where('estado_id', $estadoId)->where('nombre', $municipio)->first();
    }
}
