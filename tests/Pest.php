<?php

use Database\Seeders\PermisoSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
| Every feature test also seeds the role catalogue, because roles are reference
| data rather than something a factory is allowed to invent on the side.
|
*/

pest()
    ->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        $this->seed([RoleSeeder::class, PermisoSeeder::class]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    })
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/*
|--------------------------------------------------------------------------
| Ayudantes de las pruebas de administración
|--------------------------------------------------------------------------
|
| Viven aquí y no en un fichero de pruebas porque los usan varios a la vez, y
| un fichero de pruebas solo existe para cuando se ejecuta ese fichero.
|
*/

use App\Enums\PermisoAdminEnum;
use App\Enums\RoleEnum;
use App\Models\User;
use Database\Factories\AdminFactory;
use Spatie\Permission\Models\Role;

/**
 * Un administrador técnico, el único que entra al módulo de administradores.
 */
function administradorTecnicoDePrueba(): User
{
    $tecnico = User::factory()->create(['infocentro_id' => null]);
    $tecnico->assignRole(Role::findOrCreate(RoleEnum::AdministradorTecnico));

    // Los cuatro, como el seeder: el middleware no tiene un caso especial para el
    // técnico, así que si no los tuviera se quedaría fuera de su propio panel.
    $tecnico->syncPermissions(array_column(PermisoAdminEnum::cases(), 'value'));

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $tecnico;
}

/**
 * Un administrador general con los cuatro permisos, como los que ya existían
 * cuando se migraron.
 *
 * @param  array<string, mixed>  $atributos
 */
function administradorGeneralDePrueba(array $atributos = []): User
{
    return AdminFactory::new()->create($atributos);
}

/**
 * Un administrador general que solo puede mirar.
 */
function administradorQueSoloVe(): User
{
    return AdminFactory::new()->soloViendo()->create();
}
