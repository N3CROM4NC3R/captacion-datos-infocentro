<?php

namespace App\Support;

use App\Models\Infocentro;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Vacía la ficha de diagnóstico de un infocentro y devuelve su marca a «sin
 * guardar».
 *
 * Es una operación sin vuelta atrás: el infocentro sobrevive con su código y su
 * nombre, pero todo lo que se capturó en la ficha se pierde. Por eso el
 * proyecto no le ofrece un sitio al que volver.
 *
 * El conjunto de columnas no está escrito a mano sino que sale del modelo, para
 * que un campo nuevo de la ficha se vacíe sin tener que acordarse de añadirlo
 * aquí. Lo único que hay que declarar son las excepciones: las que
 * administra el panel, y las que son `NOT NULL` y por eso no pueden quedar a
 * `null`.
 */
class VaciarFicha
{
    /**
     * Las que definen el registro y no se pierden con la ficha.
     *
     * `codigo` lo asigna el archivo maestro y no se edita en ningún formulario.
     * `nombre` y `activo` los gestiona el panel, y además son `NOT NULL`.
     *
     * @var list<string>
     */
    private const DEL_PANEL = ['codigo', 'nombre', 'activo'];

    /**
     * Las respuestas de Sí/No de la ficha, que son `NOT NULL`.
     *
     * Al vaciar la ficha vuelven a `false`, que es su estado de «no declarado».
     * Si se dejaran a `null` el borrado reventaría contra la restricción, y con
     * razón: no es un valor que la ficha pueda tener.
     *
     * @var list<string>
     */
    private const BOOLEANAS = [
        'tiene_facilitador_titular',
        'vinculacion_autoridad',
        'vinculacion_mincyt',
        'acomp_fundacite',
        'ciencia_ciudadana',
    ];

    /**
     * Una fila por algo capturado. Los documentos además tienen su archivo.
     *
     * @var list<string>
     */
    private const TABLAS = [
        'facilitadores',
        'equipos',
        'energias',
        'actividades',
        'vinculaciones_mincyt',
        'documentos_anexos',
    ];

    public function __construct(private readonly Filesystem $disco) {}

    /**
     * Vacía la ficha y devuelve las rutas de los archivos que ha borrado.
     *
     * @return list<string>
     */
    public function ejecutar(Infocentro $infocentro): array
    {
        $rutas = $infocentro->documentosAnexos()->pluck('ruta')->all();

        DB::transaction(function () use ($infocentro): void {
            foreach (self::TABLAS as $tabla) {
                DB::table($tabla)->where('infocentro_id', $infocentro->id)->delete();
            }

            DB::table('infocentros')->where('id', $infocentro->id)->update($this->campos());
        });

        // Los archivos se van DESPUÉS de cerrar la transacción. Al revés, un
        // fallo dejaría filas sin su PDF, que es un estado del que no se puede
        // salir; así, un fallo deja la ficha entera como estaba.
        foreach ($rutas as $ruta) {
            $this->disco->delete($ruta);
        }

        return $rutas;
    }

    /**
     * How much there is to lose, for the confirmation page.
     *
     * @return array<string, int>
     */
    public function resumen(Infocentro $infocentro): array
    {
        return [
            'facilitadores' => $infocentro->facilitadores()->count(),
            'equipos' => $infocentro->equipos()->count(),
            'energias' => $infocentro->energias()->count(),
            'actividades' => $infocentro->actividades()->count(),
            'vinculaciones' => $infocentro->vinculacionesMincyt()->count(),
            'documentos' => $infocentro->documentosAnexos()->count(),
            'puntuaciones' => count((array) $infocentro->puntuaciones),
            'firmas' => count((array) $infocentro->firmas),
        ];
    }

    /**
     * The columns to clear, derived from the model.
     *
     * @return array<string, bool|null>
     */
    public function campos(): array
    {
        $campos = [];

        foreach ((new Infocentro)->getFillable() as $columna) {
            if (in_array($columna, self::DEL_PANEL, true)) {
                continue;
            }

            $campos[$columna] = in_array($columna, self::BOOLEANAS, true) ? false : null;
        }

        return $campos;
    }

    /**
     * Resolve the disk the documents live on.
     */
    public static function disco(): Filesystem
    {
        return Storage::disk(config('filesystems.default'));
    }
}
