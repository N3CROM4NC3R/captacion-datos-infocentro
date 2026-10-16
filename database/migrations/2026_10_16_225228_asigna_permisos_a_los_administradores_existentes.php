<?php

use App\Enums\PermisoAdminEnum;
use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Da los cuatro permisos a los administradores generales que ya existían.
 *
 * Hasta ahora el rol `administrador` abría todo el panel, así que una migración
 * que no concede nada dejaría a los administradores actuales sin poder ni
 * mirar el catálogo al primer despliegue. Se les da todo, que es exactamente lo
 * que tenían.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Los permisos se crean aquí, no se dan por bueno que ya estén: las
        // migraciones corren antes que los seeders, así que en un despliegue la
        // tabla `permissions` llega vacía y esta migración se iría sin hacer
        // nada. Darse por Seeders lo que necesita es confiar en un orden que
        // no está bajo su control.
        foreach (PermisoAdminEnum::cases() as $permiso) {
            Permission::findOrCreate($permiso->value);
        }

        $rol = $this->idDelRol(RoleEnum::Administrador);
        $permisos = $this->idsDePermisos();

        // El rol, en cambio, sí tiene que estar: sin él no hay administradores
        // que concederle nada, y en una instalación recién vacía esto es lo
        // normal y la migración termina aquí.
        if ($rol === null || $permisos->isEmpty()) {
            return;
        }

        $ids = DB::table('model_has_roles')
            ->where('model_type', User::class)
            ->where('role_id', $rol)
            ->pluck('model_id');

        if ($ids->isEmpty()) {
            return;
        }

        $filas = [];

        foreach ($ids as $usuario) {
            foreach ($permisos as $permisoId) {
                $filas[] = [
                    'permission_id' => $permisoId,
                    'model_type' => User::class,
                    'model_id' => $usuario,
                ];
            }
        }

        // `PermissionRegistrar` cachea qué permisos existen; sin limpiarlo, el
        // resto del proceso seguiría viendo la lista anterior.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        DB::table('model_has_permissions')->insert($filas);
    }

    /**
     * Quita los cuatro permisos a los administradores generales.
     *
     * Es destructivo y no se puede evitar: una migración de datos no sabe qué
     * permisos Leica de verdad el administrador técnico después de aplicarla, así
     * que la reversión se lleva también los suyos. En una reversión de este tipo
     * lo normal es volver a concessionarlos desde la interfaz.
     */
    public function down(): void
    {
        $rol = $this->idDelRol(RoleEnum::Administrador);
        $permisos = $this->idsDePermisos();

        if ($rol === null || $permisos->isEmpty()) {
            return;
        }

        $ids = DB::table('model_has_roles')
            ->where('model_type', User::class)
            ->where('role_id', $rol)
            ->pluck('model_id');

        DB::table('model_has_permissions')
            ->where('model_type', User::class)
            ->whereIn('permission_id', $permisos)
            ->whereIn('model_id', $ids)
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function idDelRol(RoleEnum $rol): ?int
    {
        $id = DB::table('roles')->where('name', $rol->value)->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * @return Collection<int, int>
     */
    private function idsDePermisos(): Collection
    {
        return DB::table('permissions')
            ->whereIn('name', array_column(PermisoAdminEnum::cases(), 'value'))
            ->pluck('id');
    }
};
