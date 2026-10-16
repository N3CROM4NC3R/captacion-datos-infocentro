<?php

namespace Database\Seeders;

use App\Enums\PermisoAdminEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermisoSeeder extends Seeder
{
    /**
     * Seed the permissions a general administrator can hold.
     *
     * Se crean aquí y no en la migración de datos porque son parte de las
     * referencias del sistema, igual que los roles: las pruebas los siembran
     * junto a `RoleSeeder` para no depender del orden de una migración.
     */
    public function run(): void
    {
        foreach (PermisoAdminEnum::cases() as $permiso) {
            Permission::findOrCreate($permiso->value);
        }
    }
}
