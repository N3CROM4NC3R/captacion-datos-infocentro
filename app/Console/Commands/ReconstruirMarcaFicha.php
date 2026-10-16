<?php

namespace App\Console\Commands;

use App\Enums\DimensionPerfilamientoEnum;
use App\Models\Infocentro;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Recupera la marca de «ficha guardada» de las fichas ya capturadas.
 *
 * Las columnas `diagnostico_guardado_at` y `diagnostico_actualizado_at` se
 * añadieron después de que existirieran fichas, así que todo lo capturado
 * antes de esa migración aparece como «sin ficha» aunque tenga los datos
 * enteros dentro. Este comando vuelve a poner la marca a partir de la
 * evidencia que dejaron esos datos.
 *
 * No se inventa la fecha real del guardado, que no se guardó en su momento.
 * Se estampa la última actividad de la propia ficha, que es una cota
 * inferior: si alguien editó el infocentro desde el panel después, esa
 * edición queda fuera porque no se toca `updated_at` cuando hay datos.
 */
class ReconstruirMarcaFicha extends Command
{
    /**
     * @var string
     */
    protected $signature = 'fichas:reconstruir-marca
                            {--dry-run : Informa de lo que haría sin escribir nada}
                            {--force : No pide confirmación}';

    /**
     * @var string
     */
    protected $description = 'Marca como hechas las fichas que ya estaban capturadas, según la evidencia que dejaron sus datos';

    /**
     * Las secciones que solo se rellenan desde la ficha.
     *
     * El panel de administración no las toca: ahí solo se editan nombre,
     * código, dirección, teléfono y activo. Una fila en cualquiera de estas
     * tablas es prueba de que alguien abrió la ficha.
     *
     * @var list<string>
     */
    private const TABLAS_FICHA = [
        'facilitadores',
        'equipos',
        'energias',
        'actividades',
        'vinculaciones_mincyt',
        'documentos_anexos',
    ];

    public function handle(): int
    {
        $candidatos = Infocentro::query()
            ->whereNull('diagnostico_guardado_at')
            ->get(['id', 'codigo', 'nombre', 'puntuaciones', 'estado_id', 'municipio_id', 'parroquia_id', 'updated_at']);

        if ($candidatos->isEmpty()) {
            $this->components->info('No queda ninguna ficha sin marca: no hay nada que recuperar.');

            return self::SUCCESS;
        }

        $ids = $candidatos->pluck('id');

        $conPerfilamiento = $this->conPerfilamiento($candidatos);
        $conSecciones = $this->conSecciones($ids);
        $conCascada = $candidatos->filter(fn (Infocentro $i): bool => $i->estado_id !== null
            && $i->municipio_id !== null
            && $i->parroquia_id !== null)->pluck('id');

        $aMarcar = $candidatos->filter(fn (Infocentro $i): bool => $conPerfilamiento->contains($i->id)
            || $conSecciones->contains($i->id)
            || $conCascada->contains($i->id));

        $ultimaActividad = $this->ultimaActividadPorInfocentro($ids);

        $this->informe($candidatos, $conPerfilamiento, $conSecciones, $conCascada, $aMarcar, $ultimaActividad);

        if ($this->option('dry-run')) {
            $this->components->info('Simulación: no se ha escrito nada. Quita --dry-run para aplicarlo.');

            return self::SUCCESS;
        }

        if ($aMarcar->isEmpty()) {
            $this->components->info('Ninguna ficha tiene evidencia suficiente: no se ha escrito nada.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm(sprintf(
            '¿Marcar %d fichas como hechas?',
            $aMarcar->count(),
        ), false)) {
            $this->components->warn('Cancelado: no se ha escrito nada.');

            return self::SUCCESS;
        }

        $this->marcar($aMarcar, $ultimaActividad);

        $this->components->info(sprintf('Marcadas %d fichas.', $aMarcar->count()));

        return self::SUCCESS;
    }

    /**
     * Las fichas con las siete puntuaciones del perfilamiento.
     *
     * @param  Collection<int, Infocentro>  $candidatos
     * @return Collection<int, int>
     */
    private function conPerfilamiento(Collection $candidatos): Collection
    {
        $esperadas = array_column(DimensionPerfilamientoEnum::cases(), 'value');

        return $candidatos
            ->filter(function (Infocentro $infocentro) use ($esperadas): bool {
                $puntuaciones = $infocentro->puntuaciones;

                if (! is_array($puntuaciones)) {
                    return false;
                }

                // Con una sola dimensión marcada el perfil está a medias y no
                // clasifica a nadie, así que no cuenta como evidencia.
                return array_diff($esperadas, array_keys($puntuaciones)) === [];
            })
            ->pluck('id');
    }

    /**
     * Las fichas con al menos una fila en una sección repetible.
     *
     * @param  Collection<int, int>  $ids
     * @return Collection<int, int>
     */
    private function conSecciones(Collection $ids): Collection
    {
        $conAlguna = collect();

        foreach (self::TABLAS_FICHA as $tabla) {
            $conAlguna = $conAlguna->merge(
                DB::table($tabla)->whereIn('infocentro_id', $ids)->distinct()->pluck('infocentro_id')
            );
        }

        return $conAlguna->unique()->values();
    }

    /**
     * The last moment any of the ficha's own rows was written, per infocentro.
     *
     * @param  Collection<int, int>  $ids
     * @return Collection<int, Carbon>
     */
    private function ultimaActividadPorInfocentro(Collection $ids): Collection
    {
        $ultima = collect();

        foreach (self::TABLAS_FICHA as $tabla) {
            $filas = DB::table($tabla)
                ->select('infocentro_id', DB::raw('MAX(created_at) AS ultima'))
                ->whereIn('infocentro_id', $ids)
                ->groupBy('infocentro_id')
                ->get();

            foreach ($filas as $fila) {
                $momento = Carbon::parse($fila->ultima);
                $previa = $ultima->get($fila->infocentro_id);

                if ($previa === null || $momento->gt($previa)) {
                    $ultima->put($fila->infocentro_id, $momento);
                }
            }
        }

        return $ultima;
    }

    /**
     * @param  Collection<int, int>  $conPerfilamiento
     * @param  Collection<int, int>  $conSecciones
     * @param  Collection<int, int>  $conCascada
     * @param  Collection<int, Infocentro>  $aMarcar
     * @param  Collection<int, Carbon>  $ultimaActividad
     */
    private function informe(
        Collection $candidatos,
        Collection $conPerfilamiento,
        Collection $conSecciones,
        Collection $conCascada,
        Collection $aMarcar,
        Collection $ultimaActividad,
    ): void {
        $this->newLine();
        $this->line('  Fichas sin marca, a revisar: '.$candidatos->count());
        $this->line('    · con perfilamiento completo: '.$conPerfilamiento->count());
        $this->line('    · con secciones repetibles: '.$conSecciones->count());
        $this->line('    · solo con cascada territorial: '.$conCascada->count());
        $this->line('    · a marcar como hechas: '.$aMarcar->count());
        $this->line('    · quedan pendientes: '.($candidatos->count() - $aMarcar->count()));

        if ($aMarcar->isNotEmpty()) {
            $this->newLine();
            $this->line('  Fecha que se estampará (última actividad de la ficha):');

            $muestra = $aMarcar->take(3);

            foreach ($muestra as $infocentro) {
                $this->line('    '.$this->etiqueta($infocentro).' → '.$this->fechaDe($infocentro, $ultimaActividad));
            }

            if ($aMarcar->count() > $muestra->count()) {
                $this->line('    … y '.($aMarcar->count() - $muestra->count()).' más.');
            }

            $this->guardarListado($aMarcar, $ultimaActividad);
        }

        $this->newLine();
    }

    /**
     * @param  Collection<int, Infocentro>  $aMarcar
     * @param  Collection<int, Carbon>  $ultimaActividad
     */
    private function marcar(Collection $aMarcar, Collection $ultimaActividad): void
    {
        DB::transaction(function () use ($aMarcar, $ultimaActividad): void {
            foreach ($aMarcar as $infocentro) {
                $fecha = $this->fechaDe($infocentro, $ultimaActividad);

                // Por query builder y no con save(): un guardado movería
                // updated_at a este instante, y esa es la única pista que queda
                // de cuándo se tocó el registro desde el panel. Aquí solo se
                // escriben las dos columnas de la ficha y el resto del
                // registro se queda exactamente como estaba.
                DB::table('infocentros')->where('id', $infocentro->id)->update([
                    'diagnostico_guardado_at' => $fecha,
                    'diagnostico_actualizado_at' => $fecha,
                ]);
            }
        });
    }

    /**
     * @param  Collection<int, Carbon>  $ultimaActividad
     */
    private function fechaDe(Infocentro $infocentro, Collection $ultimaActividad): Carbon
    {
        // Sin datos repetibles no hay mejor pista que la última modificación
        // del registro, aunque esa fecha puede incluir una edición del panel.
        return $ultimaActividad->get($infocentro->id) ?? Carbon::parse($infocentro->updated_at);
    }

    private function etiqueta(Infocentro $infocentro): string
    {
        return $infocentro->codigo.' · '.mb_substr((string) $infocentro->nombre, 0, 40);
    }

    /**
     * @param  Collection<int, Infocentro>  $aMarcar
     * @param  Collection<int, Carbon>  $ultimaActividad
     */
    private function guardarListado(Collection $aMarcar, Collection $ultimaActividad): void
    {
        $ruta = storage_path('app/reconstruccion-marca.txt');

        $lineas = $aMarcar->map(fn (Infocentro $i): string => $this->etiqueta($i).' → '.$this->fechaDe($i, $ultimaActividad)->toDateTimeString());

        file_put_contents($ruta, $lineas->implode(PHP_EOL).PHP_EOL);

        $this->line('  Listado completo de lo marcado: '.$ruta);
    }
}
