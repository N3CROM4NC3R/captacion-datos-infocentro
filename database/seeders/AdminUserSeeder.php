<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Seed the administration accounts.
 *
 * Dos cuentas: el administrador general y el técnico que lo gestiona. El técnico
 * es el único que puede crear más administradores generales, y no hay forma de
 * crear técnicos desde la aplicación —tampoco desde aquí—, así que su población
 * solo crece con este seeder o en la base de datos.
 *
 * Las contraseñas son fijas. Es una simplificación de carga inicial pensada para
 * poder entrar de una vez; si el sistema llega a exponerse, hay que rotarlas.
 */
class AdminUserSeeder extends Seeder
{
    /**
     * Usuario, nombre, correo, rol y contraseña inicial.
     *
     * La clave del técnico es más larga y distinta de la del general porque es
     * la cuenta que puede dar de alta y borrar otras.
     *
     * @var list<array{string, string, string, RoleEnum, string}>
     */
    private const CUENTAS = [
        ['admin', 'Administrador General', 'admin@infocentro.test', RoleEnum::Administrador, 'password'],
        ['admintecnico', 'Administrador Técnico', 'admintecnico@infocentro.test', RoleEnum::AdministradorTecnico, 'Secret123'],
    ];

    /**
     * Seed the administration accounts.
     */
    public function run(): void
    {
        foreach (self::CUENTAS as [$username, $nombre, $correo, $rol, $password]) {
            $admin = User::firstOrCreate(
                ['username' => $username],
                [
                    'name' => $nombre,
                    'email' => $correo,
                    'password' => $password,
                    'email_verified_at' => now(),
                    'infocentro_id' => null,
                ],
            );

            $admin->assignRole(Role::findOrCreate($rol));
        }
    }
}
