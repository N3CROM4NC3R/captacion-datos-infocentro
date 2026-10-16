<?php

namespace Database\Seeders;

use App\Enums\DimensionPerfilamientoEnum;
use App\Enums\EstadoEquipoEnum;
use App\Enums\FrecuenciaEnum;
use App\Enums\NivelAcademicoEnum;
use App\Enums\PrioridadIntervencionEnum;
use App\Enums\RoleEnum;
use App\Enums\TipoActividadEnum;
use App\Enums\TipoEnergiaEnum;
use App\Enums\TipoEquipoEnum;
use App\Enums\TipoInfocentroEnum;
use App\Models\Estado;
use App\Models\Infocentro;
use App\Models\Municipio;
use App\Models\Parroquia;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Rellena un puñado de fichas para poder mirar el panel con datos dentro.
 *
 * Son fichas de trabajo, con el código `PRUEBA-` por delante para que no se
 * confundan con capturas reales. Se ejecuta a mano (`db:seed --class=`), nunca
 * en `migrate` ni en `DatabaseSeeder`.
 *
 * El total y el tipo se calculan con los mismos enums que usa la ficha, no con
 * números escritos a mano: si mañana se mueve un umbral, estas fichas se mueven
 * con él.
 */
class FichasDeMuestraSeeder extends Seeder
{
    /**
     * Puntuación por dimensión, en el orden del enum: conectividad,
     * operatividad, facilitadores, equipamiento, articulación comunitaria,
     * oferta formativa y vinculación con el Mincyt.
     *
     * @var array<int, array<string, mixed>>
     */
    private const CASOS = [
        [
            'codigo' => 'PRUEBA-01',
            'nombre' => 'Infocentro de muestra — Núcleo piloto',
            'puntos' => [3, 3, 3, 3, 2, 3, 3],
            'prioridad' => null,
            'recomendaciones' => 'Oferta formativa sostenida. Conviene publicar el catálogo de actividades para la red.',
        ],
        [
            'codigo' => 'PRUEBA-02',
            'nombre' => 'Infocentro de muestra — En consolidación',
            'puntos' => [2, 2, 2, 1, 1, 2, 1],
            'prioridad' => PrioridadIntervencionEnum::CortoPlazo,
            'recomendaciones' => 'Priorizar formación para adultos mayores y contenido científico offline.',
        ],
        [
            'codigo' => 'PRUEBA-03',
            'nombre' => 'Infocentro de muestra — Por recuperar',
            'puntos' => [0, 1, 0, 0, 1, 0, 0],
            'prioridad' => PrioridadIntervencionEnum::Inmediata,
            'recomendaciones' => 'Espacio sin energía estable. Requiere articulación con Corpoelec y CANTV antes de cualquier programa.',
        ],
        [
            'codigo' => 'PRUEBA-04',
            'nombre' => 'Infocentro de muestra — ficha sin tocar desde hace meses',
            'puntos' => [1, 2, 2, 2, 2, 1, 2],
            'prioridad' => PrioridadIntervencionEnum::MedianoPlazo,
            'guardada_hace' => '4 months ago',
            'recomendaciones' => 'Sin novedad desde hace meses: comprobar si el facilitador sigue en el cargo.',
        ],
        [
            'codigo' => 'PRUEBA-05',
            'nombre' => 'Infocentro de muestra — editada hace poco',
            'puntos' => [3, 2, 3, 3, 3, 2, 2],
            'prioridad' => null,
            'guardada_hace' => '2 months ago',
            'editada_hace' => '3 days ago',
            'recomendaciones' => null,
        ],
    ];

    public function run(): void
    {
        $estado = Estado::query()->orderBy('id')->first() ?? Estado::factory()->create();
        $municipio = Municipio::query()->where('estado_id', $estado->id)->orderBy('id')->first();
        $parroquia = $municipio !== null
            ? Parroquia::query()->where('municipio_id', $municipio->id)->orderBy('id')->first()
            : null;

        foreach (self::CASOS as $caso) {
            $this->crearCaso($caso, $estado, $municipio, $parroquia);
        }
    }

    /**
     * @param  array<string, mixed>  $caso
     */
    private function crearCaso(array $caso, Estado $estado, ?Municipio $municipio, ?Parroquia $parroquia): void
    {
        $infocentro = Infocentro::query()->updateOrCreate(
            ['codigo' => $caso['codigo']],
            [
                'nombre' => $caso['nombre'],
                'activo' => true,
                'estado_id' => $estado->id,
                'municipio_id' => $municipio?->id,
                'parroquia_id' => $parroquia?->id,
                'direccion' => 'Dirección de muestra, calle sin número',
                'fecha_diagnostico' => now()->subDays(45)->toDateString(),
            ],
        );

        $this->usuario($infocentro, $caso['codigo']);

        $guardada = isset($caso['guardada_hace']) ? now()->parse($caso['guardada_hace']) : now();
        $editada = isset($caso['editada_hace']) ? now()->parse($caso['editada_hace']) : $guardada;

        $puntuaciones = $this->puntuaciones($caso['puntos']);
        $total = array_sum($puntuaciones);

        $infocentro->update([
            'puntuaciones' => $puntuaciones,
            'puntuacion_total' => $total,
            'tipo_infocentro' => TipoInfocentroEnum::desdePuntuacion($total),
            'prioridad_intervencion' => $caso['prioridad'],
            'recomendaciones' => $caso['recomendaciones'],
            'diagnostico_guardado_at' => $guardada,
            'diagnostico_actualizado_at' => $editada,
        ]);

        $this->inventario($infocentro, $caso['puntos']);

        $this->command?->info(sprintf(
            '  %s → %d/21, %s',
            $caso['codigo'],
            $total,
            TipoInfocentroEnum::desdePuntuacion($total)->label(),
        ));
    }

    /**
     * @param  array<int, int>  $puntos
     * @return array<string, int>
     */
    private function puntuaciones(array $puntos): array
    {
        $mapa = [];

        foreach (DimensionPerfilamientoEnum::cases() as $indice => $dimension) {
            $mapa[$dimension->value] = $puntos[$indice] ?? 0;
        }

        return $mapa;
    }

    private function usuario(Infocentro $infocentro, string $codigo): void
    {
        $username = 'muestra'.strtolower($codigo);

        $usuario = User::query()->where('username', $username)->first() ?? new User;

        $usuario->fill([
            'name' => 'Usuario de muestra',
            'username' => $username,
            'email' => $username.'@infocentro.test',
            'password' => Hash::make('muestra'),
            'email_verified_at' => now(),
        ])->save();

        // `forceFill` porque `infocentro_id` está en el fillable pero el
        // usuario aún no está asociado a nada al crearse.
        $usuario->forceFill(['infocentro_id' => $infocentro->id])->save();
        $usuario->assignRole(Role::findOrCreate(RoleEnum::Infocentro));
    }

    /**
     * Deja algo de inventario y de facilitadores para que la consulta de solo
     * lectura tenga algo que enseñar más allá de la cabecera.
     *
     * @param  array<int, int>  $puntos
     */
    private function inventario(Infocentro $infocentro, array $puntos): void
    {
        $infocentro->facilitadores()->delete();
        $infocentro->facilitadores()->createMany([
            ['nombre' => 'Facilitador de muestra', 'nivel_academico' => NivelAcademicoEnum::Universitario, 'titulo_carrera' => 'Ingeniería en Computación', 'anios_experiencia' => 2, 'formacion_tic' => true, 'formacion_ciencias' => true, 'horas_semanales' => 12],
        ]);

        $infocentro->equipos()->delete();
        $infocentro->equipos()->createMany([
            ['tipo' => TipoEquipoEnum::Computadoras, 'cantidad' => 6, 'funcionando' => 5, 'fuera_servicio' => 1, 'estado' => EstadoEquipoEnum::Bueno],
            ['tipo' => TipoEquipoEnum::KitsRobotica, 'cantidad' => 2, 'funcionando' => 2, 'fuera_servicio' => 0, 'estado' => EstadoEquipoEnum::Regular],
        ]);

        $infocentro->energias()->delete();
        $infocentro->energias()->createMany([
            ['tipo' => TipoEnergiaEnum::Corpoelec, 'estado' => EstadoEquipoEnum::Regular],
            ['tipo' => TipoEnergiaEnum::PanelesSolares, 'cantidad' => 2, 'estado' => EstadoEquipoEnum::Bueno],
        ]);

        $infocentro->actividades()->delete();
        $infocentro->actividades()->createMany([
            ['tipo' => TipoActividadEnum::Robotica, 'activa' => true, 'frecuencia' => FrecuenciaEnum::Semanal, 'participantes' => 24],
            ['tipo' => TipoActividadEnum::CienciasBasicas, 'activa' => true, 'frecuencia' => FrecuenciaEnum::Quincenal, 'participantes' => 30],
        ]);

        DB::table('infocentros')->where('id', $infocentro->id)->update(['updated_at' => now()]);
    }
}
