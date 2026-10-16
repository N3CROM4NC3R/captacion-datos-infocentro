<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use App\Models\Infocentro;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class InfocentroController extends Controller
{
    /**
     * Display a listing of the infocentros.
     *
     * Los filtros van en la query y no en el navegador: así el estado queda
     * en la URL, se puede compartir y sobrevive a la paginación.
     */
    public function index(Request $request): View
    {
        $ficha = $request->query('ficha');
        $busqueda = trim((string) $request->query('q', ''));

        $infocentros = Infocentro::query()
            ->with('user')
            ->when($ficha === 'hecha', fn ($query) => $query->whereNotNull('diagnostico_guardado_at'))
            ->when($ficha === 'pendiente', fn ($query) => $query->whereNull('diagnostico_guardado_at'))
            ->when($busqueda !== '', fn ($query) => $query->where(function ($interno) use ($busqueda): void {
                // `LOWER(...) LIKE` en vez de `ilike`: la base de producción es
                // PostgreSQL pero las pruebas corren sobre SQLite, y `ilike` no
                // existe ahí. Bajar las dos partes cumple en los dos motores.
                $patron = '%'.mb_strtolower($busqueda).'%';
                $interno->whereRaw('LOWER(nombre) LIKE ?', [$patron])
                    ->orWhereRaw('LOWER(codigo) LIKE ?', [$patron]);
            }))
            ->orderBy('nombre')
            ->paginate(15)
            // Sin esto, `links()` regenera la paginación y tira los filtros.
            ->withQueryString();

        return view('admin.infocentros.index', [
            'infocentros' => $infocentros,
            'ficha' => in_array($ficha, ['hecha', 'pendiente'], true) ? $ficha : '',
            'busqueda' => $busqueda,
            'conFicha' => Infocentro::whereNotNull('diagnostico_guardado_at')->count(),
            'total' => Infocentro::count(),
        ]);
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
