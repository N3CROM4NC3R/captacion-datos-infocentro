<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the general administrator account.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrador General',
                'email' => 'admin@infocentro.test',
                'password' => 'password',
                'email_verified_at' => now(),
                'infocentro_id' => null,
            ],
        );

        $admin->assignRole(Role::findOrCreate(RoleEnum::Administrador));
    }
}
