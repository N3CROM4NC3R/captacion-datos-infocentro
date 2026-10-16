<?php

namespace Database\Factories;

use App\Enums\PermisoAdminEnum;
use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates a user for the general administrator, who has no infocentro.
 *
 * @extends Factory<User>
 */
#[UseModel(User::class)]
class AdminFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'infocentro_id' => null,
        ];
    }

    /**
     * Apply the administrator role and its permissions once the user exists.
     *
     * Los cuatro, que es como queda un administrador general real tras la
     * migración que los *_asigna*_a los que ya existían. Un administrador con
     * menos se construye con `conPermisos()`.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (User $user): void {
            $user->assignRole(Role::findOrCreate(RoleEnum::Administrador));
            $user->syncPermissions(array_column(PermisoAdminEnum::cases(), 'value'));
        });
    }

    /**
     * Indicate that this administrator only holds the given permissions.
     */
    public function conPermisos(array $permisos): static
    {
        return $this->afterCreating(function (User $user) use ($permisos): void {
            // Vaciar la lista en vez de añadir: `configure()` deja los cuatro y
            // este estado se usa justamente para probar que faltan.
            $user->syncPermissions($permisos);

            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });
    }

    /**
     * Indicate that this administrator can only look, nothing else.
     */
    public function soloViendo(): static
    {
        return $this->conPermisos(PermisoAdminEnum::deAcceso());
    }

    /**
     * Indicate that the user's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }
}
