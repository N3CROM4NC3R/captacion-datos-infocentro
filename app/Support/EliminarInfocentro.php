<?php

namespace App\Support;

use App\Models\Infocentro;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Borra un infocentro del catálogo con todo lo que cuelga de él: su cuenta de
 * acceso y su ficha de diagnóstico.
 *
 * Es una operación sin vuelta atrás. Lo único que sobrevive es el catálogo,
 * que se puede volver a cargar desde `database/data/infocentros.xlsx` con
 * `db:seed --class=InfocentrosArchivoSeeder`; lo que eso no devuelve es la
 * ficha ni las direcciones, así que no es una red, es un parche.
 */
class EliminarInfocentro
{
    public function __construct(private readonly Filesystem $disco) {}

    /**
     * Borra el infocentro y devuelve cuántos ficheros han desaparecido del disco.
     */
    public function ejecutar(Infocentro $infocentro): int
    {
        $rutas = $infocentro->documentosAnexos()->pluck('ruta')->all();

        DB::transaction(function () use ($infocentro): void {
            // El usuario va primero. Su clave foránea es `SET NULL`, así que
            // borrado al revés dejaría a la persona entrando al sistema con una
            // cuenta que ya no lleva a ningún infocentro. Spatie desengancha los
            // roles en su propio evento `deleting`, así que no hay que hacerlo a
            // mano.
            $infocentro->user?->delete();

            // Las seis tablas de la ficha caen en cascada. Los ficheros, no: la
            // cascada solo ve la base de datos, así que los PDF se borran
            // después de cerrar la transacción.
            $infocentro->delete();
        });

        foreach ($rutas as $ruta) {
            $this->disco->delete($ruta);
        }

        return count($rutas);
    }

    /**
     * Resolve the disk the documents live on.
     */
    public static function disco(): Filesystem
    {
        return Storage::disk(config('filesystems.default'));
    }
}
