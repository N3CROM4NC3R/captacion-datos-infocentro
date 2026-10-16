<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PrioridadIntervencionEnum;
use App\Enums\TipoInfocentroEnum;
use App\Http\Controllers\Concerns\PresentaFicha;
use App\Http\Controllers\Controller;
use App\Models\Infocentro;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Consulta de las fichas de diagnóstico ya capturadas.
 *
 * Es de solo lectura: el panel existe para ver qué se ha capturado, no para
 * corregirlo. Editar sigue siendo cosa del panel de infocentros y de quien
 * lleva la ficha.
 */
class FichasController extends Controller
{
    use PresentaFicha;

    /**
     * Display the captured diagnosis records.
     *
     * Sin filtro de estado porque solo se listan las fichas hechas: el filtro
     * de «sin ficha» vive en el listado de infocentros, que es donde se busca
     * lo que falta por hacer.
     */
    public function index(Request $request): View
    {
        $tipo = $request->query('tipo');
        $prioridad = $request->query('prioridad');
        $busqueda = trim((string) $request->query('q', ''));

        $fichas = Infocentro::query()
            ->whereNotNull('diagnostico_guardado_at')
            ->with(['estado', 'municipio'])
            ->when(in_array($tipo, array_column(TipoInfocentroEnum::cases(), 'value'), true), fn ($query) => $query->where('tipo_infocentro', $tipo))
            ->when(in_array($prioridad, array_column(PrioridadIntervencionEnum::cases(), 'value'), true), fn ($query) => $query->where('prioridad_intervencion', $prioridad))
            ->when($busqueda !== '', fn ($query) => $query->where(function ($interno) use ($busqueda): void {
                // `LOWER(...) LIKE` en vez de `ilike`: la base de producción es
                // PostgreSQL pero las pruebas corren sobre SQLite, y `ilike` no
                // existe ahí. Bajar las dos partes cumple en los dos motores.
                $patron = '%'.mb_strtolower($busqueda).'%';
                $interno->whereRaw('LOWER(nombre) LIKE ?', [$patron])
                    ->orWhereRaw('LOWER(codigo) LIKE ?', [$patron]);
            }))
            ->orderByDesc('diagnostico_guardado_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.fichas.index', [
            'fichas' => $fichas,
            'tipo' => in_array($tipo, array_column(TipoInfocentroEnum::cases(), 'value'), true) ? $tipo : '',
            'prioridad' => in_array($prioridad, array_column(PrioridadIntervencionEnum::cases(), 'value'), true) ? $prioridad : '',
            'busqueda' => $busqueda,
            'totalFichas' => Infocentro::whereNotNull('diagnostico_guardado_at')->count(),
            'porTipo' => collect(TipoInfocentroEnum::cases())
                ->mapWithKeys(fn (TipoInfocentroEnum $t): array => [
                    $t->value => Infocentro::where('tipo_infocentro', $t->value)->count(),
                ])->all(),
        ]);
    }

    /**
     * Show one infocentro's diagnosis record, read only.
     *
     * Reutiliza los partials de la ficha dentro de un `fieldset disabled` en
     * vez de duplicar nueve secciones de maquetación. El payload de Alpine se
     * publica igual: sin él, lo que usa `x-cloak` —los campos condicionales
     * que sí están rellenados— se quedaría oculto para siempre.
     */
    public function show(Infocentro $infocentro): View
    {
        return view('admin.fichas.show', $this->datosFicha($infocentro));
    }
}
