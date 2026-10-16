<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PermisoAdminEnum;
use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Gestión de las cuentas de administrador general.
 *
 * Solo el administrador técnico entra aquí: puede dar de alta, corregir y
 * borrar administradores generales, pero no crear más técnicos. Esa es
 * justamente lo que evita la escalada —si un técnico pudiera repetirse a sí
 * mismo, el módulo perdería su sentido— y significa que este rol solo crece
 * desde la base de datos o desde el seeder.
 */
class AdministradorController extends Controller
{
    /**
     * Display the general administrator accounts.
     *
     * Solo los generales: son los únicos gestionables, y una fila que no se
     * puede tocar solo genera la pregunta de por qué.
     */
    public function index(Request $request): View
    {
        $administradores = $this->administradoresGenerales()->orderBy('name')->paginate(15);

        return view('admin.administradores.index', [
            'administradores' => $administradores,
            'tecnico' => $request->user(),
            // Indexado por id para que el componente de Alpine sepa a cuál se
            // pulsó, en un bloque de JSON en vez de quince copias del modal.
            'detalle' => $administradores->getCollection()->mapWithKeys(fn (User $admin): array => [
                $admin->id => [
                    'nombre' => $admin->name,
                    'username' => $admin->username,
                ],
            ])->all(),
        ]);
    }

    /**
     * Show the form for creating an administrator.
     */
    public function create(): View
    {
        return view('admin.administradores.create');
    }

    /**
     * Store a newly created administrator.
     *
     * El rol se asigna aquí y no viene del formulario: que el payload pudiera
     * decidir el rol convertiría este formulario en una escalada de permisos.
     */
    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate($this->reglas());

        $admin = User::create([
            'name' => $datos['name'],
            'username' => $datos['username'],
            'email' => $datos['email'],
            'password' => $datos['password'],
            // `infocentro_id` no va: no es fillable en `User`, así que ponerlo
            // aquí dentro se descartaría en silencio. La columna ya viene a nulo.
            'email_verified_at' => now(),
        ]);

        $admin->assignRole(Role::findOrCreate(RoleEnum::Administrador));
        $this->asignarPermisos($admin, $request->input('permisos', []));

        return redirect()
            ->route('admin.administradores.index')
            ->with('status', "Administrador general «{$admin->username}» creado.");
    }

    /**
     * Show the form for editing the given administrator.
     */
    public function edit(User $administrador): View
    {
        return view('admin.administradores.edit', ['administrador' => $administrador]);
    }

    /**
     * Update the given administrator.
     *
     * La contraseña es opcional: sin ella se deja la que hay, que es lo que
     * hace falta para corregir un nombre o un correo sin obligar a cambiar algo
     * que el técnico no conoce.
     */
    public function update(Request $request, User $administrador): RedirectResponse
    {
        $datos = $request->validate($this->reglas($administrador));

        $administrador->fill([
            'name' => $datos['name'],
            'username' => $datos['username'],
            'email' => $datos['email'],
        ]);

        if (filled($datos['password'] ?? null)) {
            $administrador->password = $datos['password'];
        }

        $administrador->save();
        $this->asignarPermisos($administrador, $request->input('permisos', []));

        return redirect()
            ->route('admin.administradores.index')
            ->with('status', "Administrador general «{$administrador->username}» actualizado.");
    }

    /**
     * Delete the given administrator.
     *
     * El enlace de ruta solo resuelve administradores generales, así que un
     * técnico puro ya recibe 404 antes de llegar aquí. Este 403 cubre el único
     * estado en el que el borrado sería posible: alguien con los dos roles a la
     * vez, que se quedaría fuera del panel sin nadie que se lo devolviera.
     */
    public function destroy(Request $request, User $administrador): RedirectResponse
    {
        abort_if($administrador->is($request->user()), Response::HTTP_FORBIDDEN);

        $username = $administrador->username;

        // Spatie desengancha los roles en su evento `deleting`, así que la fila
        // no deja asignaciones colgando de un usuario que ya no existe.
        $administrador->delete();

        return redirect()
            ->route('admin.administradores.index')
            ->with('status', "Administrador general «{$username}» eliminado.");
    }

    /**
     * Sync the permissions a general administrator holds.
     *
     * Vienen del formulario y se filtran contra el enum antes de tocar nada: un
     * `permisos` con un nombre que no existe no llega a `syncPermissions` ni
     * podría colarse en la tabla de permisos de Spatie.
     *
     * @param  array<int, string>  $pedidos
     */
    private function asignarPermisos(User $administrador, array $pedidos): void
    {
        $validos = collect(PermisoAdminEnum::cases())
            ->filter(fn (PermisoAdminEnum $permiso): bool => in_array($permiso->value, $pedidos, true))
            ->map(fn (PermisoAdminEnum $permiso): string => $permiso->value)
            ->all();

        $administrador->syncPermissions($validos);

        // Spatie cachea la lista de permisos del proceso. Sin limpiarlo, el
        // cambio no se vería hasta que esa caché se vaciara por otra vía, que
        // es justo el forgotCachedPermissions que ya hace el proyecto con los
        // roles.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Las cuentas que este módulo sabe gestionar.
     */
    private function administradoresGenerales(): Builder
    {
        return User::role(RoleEnum::Administrador)
            // Sin infocentro: una cuenta con rol de administrador y centro
            // asignado sería una tercera cosa, y aquí no se gestiona.
            ->whereNull('infocentro_id');
    }

    /**
     * The validation rules for creating or updating an administrator.
     *
     * @return array<string, array<int, mixed>>
     */
    private function reglas(?User $administrador = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required', 'string', 'max:255',
                Rule::unique(User::class, 'username')->ignore($administrador?->id),
            ],
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique(User::class, 'email')->ignore($administrador?->id),
            ],
            'password' => [
                // En el alta es obligatoria; al corregir, no: se deja la que hay.
                $administrador ? 'nullable' : 'required',
                'confirmed', Password::defaults(),
            ],
        ];
    }
}
