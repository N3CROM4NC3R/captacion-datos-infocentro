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
     */
    public function index(): View
    {
        $infocentros = Infocentro::query()
            ->with('user')
            ->orderBy('nombre')
            ->paginate(15);

        return view('admin.infocentros.index', [
            'infocentros' => $infocentros,
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
