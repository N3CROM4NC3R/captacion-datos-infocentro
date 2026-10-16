<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleEnum;
use App\Enums\TipoInfocentroEnum;
use App\Http\Controllers\Controller;
use App\Models\Estado;
use App\Models\Infocentro;
use App\Models\User;
use App\Support\EliminarInfocentro;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class InfocentroController extends Controller
{
    /**
     * Los únicos parámetros que se reenvían al volver de borrar un infocentro.
     *
     * Tiene que ir en la misma clave que la lista `filtros` del componente Alpine
     * que arma el `action` del modal: si una de las dos se queda corta, borrar
     * devuelve al listado sin filtros y sin ningún error visible.
     *
     * Una lista corta a propósito: `redirect()` no debe recibir nada que venga
     * de la petición sin decidir, y en este listado solo hay estos tres.
     *
     * @var list<string>
     */
    private const FILTROS_LISTADO = ['nombre', 'codigo', 'estado', 'estatus', 'clasificacion', 'ficha', 'page'];

    /**
     * Display a listing of the infocentros.
     *
     * Los filtros van en la query y no en el navegador: así el estado queda
     * en la URL, se puede compartir y sobrevive a la paginación.
     */
    public function index(Request $request): View
    {
        $ficha = $request->query('ficha');
        $nombre = trim((string) $request->query('nombre', ''));
        $codigo = trim((string) $request->query('codigo', ''));
        $estatus = $request->query('estatus');
        $clasificacion = $request->query('clasificacion');

        // El estado territorial se valida contra la lista real, igual que los
        // desplegables: un `?estado=inventado` se ignora en vez de meter un id
        // cualquiera en la consulta.
        //
        // La comparación va de string a string. `in_array` en modo estricto
        // compararía `'1'` contra el `1` entero de la base y nunca casaría,
        // con lo que el filtro no filtraría nada y parecería puesto.
        $estados = Estado::orderBy('nombre')->get(['id', 'nombre']);
        $pedido = (string) $request->query('estado');
        $estado = in_array($pedido, $estados->pluck('id')->map(strval(...))->all(), true) ? $pedido : '';

        $infocentros = Infocentro::query()
            ->with('user')
            // Los contadores que enseña el modal de eliminar. Con `withCount`
            // son seis consultas agregadas para toda la página, no una por fila
            // y por tabla.
            ->withCount([
                'facilitadores', 'equipos', 'energias',
                'actividades', 'vinculacionesMincyt', 'documentosAnexos',
            ])
            ->when($ficha === 'hecha', fn ($query) => $query->whereNotNull('diagnostico_guardado_at'))
            ->when($ficha === 'pendiente', fn ($query) => $query->whereNull('diagnostico_guardado_at'))
            ->when($nombre !== '', fn ($query) => $query->whereRaw(
                'LOWER(nombre) LIKE ?',
                [$this->patron($nombre)],
            ))
            ->when($codigo !== '', fn ($query) => $query->whereRaw(
                'LOWER(codigo) LIKE ?',
                [$this->patron($codigo)],
            ))
            ->when($estado !== '', fn ($query) => $query->where('estado_id', $estado))
            ->when($estatus === 'activo', fn ($query) => $query->where('activo', true))
            ->when($estatus === 'inactivo', fn ($query) => $query->where('activo', false))
            ->when(
                in_array($clasificacion, array_column(TipoInfocentroEnum::cases(), 'value'), true),
                fn ($query) => $query->where('tipo_infocentro', $clasificacion),
            )
            ->orderBy('nombre')
            ->paginate(15)
            // Sin esto, `links()` regenera la paginación y tira los filtros.
            ->withQueryString();

        $fichaLimpia = in_array($ficha, ['hecha', 'pendiente'], true) ? $ficha : '';
        $estatusLimpio = in_array($estatus, ['activo', 'inactivo'], true) ? $estatus : '';
        $clasificacionLimpia = in_array($clasificacion, array_column(TipoInfocentroEnum::cases(), 'value'), true) ? $clasificacion : '';

        return view('admin.infocentros.index', [
            'infocentros' => $infocentros,
            'ficha' => $fichaLimpia,
            'nombre' => $nombre,
            'codigo' => $codigo,
            'estados' => $estados,
            'estado' => $estado,
            'estatus' => $estatusLimpio,
            'clasificacion' => $clasificacionLimpia,
            // Solo se ofrece «Limpiar» si hay algo que limpiar: un enlace que no
            // hace nada es peor que no tenerlo.
            'hayFiltros' => $nombre !== ''
                || $codigo !== ''
                || $estado !== ''
                || $estatusLimpio !== ''
                || $clasificacionLimpia !== ''
                || $fichaLimpia !== '',
            'conFicha' => Infocentro::whereNotNull('diagnostico_guardado_at')->count(),
            'total' => Infocentro::count(),
            'detalle' => $this->detalleParaModal($infocentros),
        ]);
    }

    /**
     * The pattern a text filter matches on.
     *
     * `LOWER(...) LIKE` en vez de `ilike`: la base de producción es PostgreSQL
     * pero las pruebas corren sobre SQLite, y `ilike` no existe ahí. Bajar las
     * dos partes cumple en los dos motores.
     */
    private function patron(string $texto): string
    {
        return '%'.mb_strtolower($texto).'%';
    }

    /**
     * Lo que el modal de eliminar necesita saber de cada fila.
     *
     * Va indexado por id para que el componente de Alpine lo busque por la que
     * se pulsó, y en un único bloque de JSON en vez de quince copias del
     * marcado del modal.
     *
     * @param  LengthAwarePaginator<int, Infocentro>  $infocentros
     * @return array<int|string, array<string, mixed>>
     */
    private function detalleParaModal($infocentros): array
    {
        return $infocentros->getCollection()->mapWithKeys(fn (Infocentro $infocentro): array => [
            $infocentro->id => [
                'nombre' => $infocentro->nombre,
                'codigo' => $infocentro->codigo,
                'usuario' => $infocentro->user?->username,
                'ficha' => $infocentro->diagnostico_guardado_at !== null,
                'facilitadores' => $infocentro->facilitadores_count,
                'equipos' => $infocentro->equipos_count,
                'energias' => $infocentro->energias_count,
                'actividades' => $infocentro->actividades_count,
                'vinculaciones' => $infocentro->vinculaciones_mincyt_count,
                'documentos' => $infocentro->documentos_anexos_count,
            ],
        ])->all();
    }

    /**
     * Delete an infocentro with its account and its ficha.
     *
     * Sin vuelta atrás: no hay copia ni papelera. El catálogo se puede recargar
     * desde el archivo maestro, pero eso no devuelve la ficha.
     */
    public function destroy(Request $request, Infocentro $infocentro, EliminarInfocentro $eliminar): RedirectResponse
    {
        $codigo = $infocentro->codigo;
        $usuario = $infocentro->user?->username;
        $borrados = $eliminar->ejecutar($infocentro);

        $aviso = "Infocentro eliminado: $codigo.";

        if ($usuario !== null) {
            // `{$usuario}` y no `$usuario`: PHP cuenta los bytes altos como
            // caracteres de identificador, así que con la comilla angular justo
            // detrás el nombre de la variable salía siendo `usuario\xc2bb` y
            // reventaba con «variable no definida».
            $aviso .= " También se ha borrado su cuenta «{$usuario}».";
        }

        if ($borrados > 0) {
            $aviso .= match ($borrados) {
                1 => ' Y su documento adjunto.',
                default => " Y sus $borrados documentos adjuntos.",
            };
        }

        return redirect()
            ->route('admin.infocentros.index', $request->only(self::FILTROS_LISTADO))
            ->with('status', $aviso);
    }

    /**
     * Show the form for creating a new infocentro.
     */
    public function create(): View
    {
        return view('admin.infocentros.create');
    }

    /**
     * Store a newly created infocentro along with its user account.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'codigo' => ['required', 'string', 'max:255', Rule::unique(Infocentro::class, 'codigo')],
            'direccion' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:255'],
            'activo' => ['boolean'],
            'user_name' => ['required', 'string', 'max:255'],
            'user_username' => ['required', 'string', 'max:255', Rule::unique(User::class, 'username')],
            'user_email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'user_password' => ['required', 'confirmed', Password::defaults()],
        ]);

        DB::transaction(function () use ($validated, $request): void {
            $infocentro = Infocentro::create([
                'nombre' => $validated['nombre'],
                'codigo' => $validated['codigo'],
                'direccion' => $validated['direccion'] ?? null,
                'telefono' => $validated['telefono'] ?? null,
                'activo' => $request->boolean('activo'),
            ]);

            $user = $infocentro->user()->create([
                'name' => $validated['user_name'],
                'username' => $validated['user_username'],
                'email' => $validated['user_email'],
                'password' => $validated['user_password'],
            ]);

            $user->assignRole(RoleEnum::Infocentro);
        });

        return redirect()
            ->route('admin.infocentros.index')
            ->with('status', __('Infocentro created successfully.'));
    }

    /**
     * Show the form for editing the given infocentro.
     */
    public function edit(Infocentro $infocentro): View
    {
        $infocentro->load('user');

        return view('admin.infocentros.edit', [
            'infocentro' => $infocentro,
        ]);
    }

    /**
     * Update the given infocentro and its user account.
     */
    public function update(Request $request, Infocentro $infocentro): RedirectResponse
    {
        $user = $infocentro->user()->firstOrNew();

        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'codigo' => ['required', 'string', 'max:255', Rule::unique(Infocentro::class, 'codigo')->ignore($infocentro->id)],
            'direccion' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:255'],
            'activo' => ['boolean'],
            'user_name' => ['required', 'string', 'max:255'],
            'user_username' => ['required', 'string', 'max:255', Rule::unique(User::class, 'username')->ignore($user->getKey())],
            'user_email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($user->getKey())],
            'user_password' => [
                $user->exists ? 'nullable' : 'required',
                'confirmed',
                Password::defaults(),
            ],
        ]);

        DB::transaction(function () use ($validated, $request, $infocentro, $user): void {
            $infocentro->update([
                'nombre' => $validated['nombre'],
                'codigo' => $validated['codigo'],
                'direccion' => $validated['direccion'] ?? null,
                'telefono' => $validated['telefono'] ?? null,
                'activo' => $request->boolean('activo'),
            ]);

            $attributes = [
                'name' => $validated['user_name'],
                'username' => $validated['user_username'],
                'email' => $validated['user_email'],
            ];

            if (filled($validated['user_password'] ?? null)) {
                $attributes['password'] = $validated['user_password'];
            }

            $user->fill($attributes)->save();

            if ($user->wasRecentlyCreated) {
                $user->assignRole(RoleEnum::Infocentro);
            }
        });

        return redirect()
            ->route('admin.infocentros.index')
            ->with('status', __('Infocentro updated successfully.'));
    }
}
