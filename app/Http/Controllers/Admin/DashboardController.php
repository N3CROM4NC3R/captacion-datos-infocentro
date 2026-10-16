<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TipoInfocentroEnum;
use App\Http\Controllers\Controller;
use App\Models\Infocentro;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the general administrator dashboard.
     *
     * Tres consultas para los seis números, y no una por número: el panel se
     * abre mucho y cinco `count()` sueltos son cinco idas a la base para
     * pintar seis cifras.
     *
     * Los recuentos van con `where(..., true)` y no agrupando por la columna
     * booleana: PostgreSQL y SQLite devuelven la clave del grupo de forma
     * distinta —`true`/`false` frente a `1`/`0`, y la nula como cadena vacía— y
     * leer el grupo por clave solo funcionaría en uno de los dos motores.
     */
    public function __invoke(): View
    {
        $activos = Infocentro::where('activo', true)->count();
        $inactivos = Infocentro::where('activo', false)->count();

        $porTipo = DB::table('infocentros')
            ->selectRaw('tipo_infocentro, count(*) AS total')
            ->groupBy('tipo_infocentro')
            ->get();

        // `tipo_infocentro IS NULL` en vez de comparar con `null`: en SQL,
        // `= NULL` nunca es cierto y el filtro no filtraría nada.
        $sinClasificar = (int) DB::table('infocentros')->whereNull('tipo_infocentro')->count();

        return view('admin.dashboard', [
            // `activo` es NOT NULL, así que los dos recuentos suman el total y
            // no hace falta una tercera consulta para saberlo.
            'total' => $activos + $inactivos,
            'activos' => $activos,
            'inactivos' => $inactivos,
            // Se recorre el enum y no las filas de la consulta: si mañana se
            // añade un tipo, la ficha aparece sola en el panel.
            'porTipo' => collect(TipoInfocentroEnum::cases())
                ->mapWithKeys(fn (TipoInfocentroEnum $tipo): array => [
                    $tipo->value => (int) ($porTipo->firstWhere('tipo_infocentro', $tipo->value)->total ?? 0),
                ])
                ->all(),
            'sinClasificar' => $sinClasificar,
        ]);
    }
}
