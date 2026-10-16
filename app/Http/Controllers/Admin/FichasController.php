<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PrioridadIntervencionEnum;
use App\Enums\TipoInfocentroEnum;
use App\Http\Controllers\Concerns\PresentaFicha;
use App\Http\Controllers\Controller;
use App\Models\Infocentro;
use App\Support\VaciarFicha;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Consulta de las fichas de diagnóstico ya capturadas.
 *
 * De solo lectura, con una excepción: vaciar la ficha entera. No hay edición —
corregir sigue siendo cosa del panel de infocentros y de quien lleva la ficha—
 pero sí hay una forma de deshacer una captura que se hizo por error o que se
 * capturó sobre un infocentro que no era. Sin vuelta atrás.
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

    /**
     * Ask for confirmation before discarding a ficha.
     *
     * El botón de eliminar está en la fila del listado, así que esta página es
     * la única barrera. Por eso enseña qué se va a perder en vez de limitarse a
     * un sí o no a ciegas.
     */
    public function eliminar(Request $request, Infocentro $infocentro, VaciarFicha $vaciar): View
    {
        $this->exigirFicha($infocentro);

        return view('admin.fichas.eliminar', [
            'infocentro' => $infocentro,
            'resumen' => $vaciar->resumen($infocentro),
            ...$this->origen($request),
        ]);
    }

    /**
     * Discard a ficha, leaving the infocentro in the catalog.
     *
     * Sin vuelta atrás: no hay copia ni papelera, y el proyecto no tiene soft
     * deletes en ninguna otra parte. El infocentro sobrevive con su código y su
     * nombre y vuelve a la lista de pendientes.
     */
    public function destroy(Request $request, Infocentro $infocentro, VaciarFicha $vaciar): RedirectResponse
    {
        $this->exigirFicha($infocentro);

        $destino = $this->origen($request);
        $codigo = $infocentro->codigo;
        $borrados = count($vaciar->ejecutar($infocentro));

        $aviso = "Ficha eliminada. $codigo ha vuelto a la lista de pendientes.";

        if ($borrados > 0) {
            $aviso .= match ($borrados) {
                1 => ' Se ha borrado su documento adjunto.',
                default => " Se han borrado sus $borrados documentos adjuntos.",
            };
        }

        return redirect()
            ->route($destino['ruta'], $destino['filtros'])
            ->with('status', $aviso);
    }

    /**
     * El botón está en las filas de los dos listados, así que hay que saber de
     * cuál se pulsó para devolver al sitio de origen con sus filtros puestos.
     *
     * Los destinos están escritos aquí y no vienen del navegador: `redirect()`
     * recibe siempre el nombre de una ruta de esta lista, así que no hay forma
     * de mandar a alguien a otro sitio. Los filtros que se reenvían son una
     * lista corta por destino, no todo lo que venga en la petición.
     *
     * @var array<string, array{ruta: string, filtros: list<string>}>
     */
    private const ORIGENES = [
        'fichas' => [
            'ruta' => 'admin.fichas.index',
            'filtros' => ['q', 'tipo', 'prioridad', 'page'],
        ],
        'infocentros' => [
            'ruta' => 'admin.infocentros.index',
            // Los mismos nombres que usa el listado de infocentros; si se
            // quedara uno corto, la confirmación devolvería sin ese filtro.
            'filtros' => ['nombre', 'codigo', 'estado', 'estatus', 'clasificacion', 'ficha', 'page'],
        ],
    ];

    /**
     * Both pages only make sense for a ficha that actually exists.
     *
     * El botón solo se pinta en las filas que tienen ficha, pero la ruta se
     * puede escribir a mano. Sin esta comprobación, un infocentro del catálogo
     * sin ficha podría acabar perdiendo la dirección y el teléfono que gestiona
     * el panel, sin haber tenido nunca ficha que vaciar.
     */
    private function exigirFicha(Infocentro $infocentro): void
    {
        abort_if($infocentro->diagnostico_guardado_at === null, Response::HTTP_NOT_FOUND);
    }

    /**
     * Resolve where the admin came from, defaulting to the fichas module.
     *
     * `query()` y no `input()` a propósito: el origen viaja en la URL del
     * formulario y no en un campo oculto, porque el navegador rellena los
     * ocultos con lo que autocompletó antes y mandaría al admin a un listado
     * que no es de donde salió.
     *
     * @return array{origen: string, ruta: string, filtros: array<string, mixed>}
     */
    private function origen(Request $request): array
    {
        $pedido = (string) $request->query('origen', 'fichas');
        $origen = array_key_exists($pedido, self::ORIGENES) ? $pedido : 'fichas';

        return [
            'origen' => $origen,
            'ruta' => self::ORIGENES[$origen]['ruta'],
            'filtros' => $request->only(self::ORIGENES[$origen]['filtros']),
        ];
    }
}
