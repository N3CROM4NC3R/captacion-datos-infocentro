<?php

namespace Database\Factories;

use App\Enums\RoleEnum;
use App\Models\Infocentro;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Creates a user bound to its own freshly created infocentro.
 *
 * @extends Factory<User>
 */
#[UseModel(User::class)]
class InfocentroUserFactory extends Factory
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
            'infocentro_id' => Infocentro::factory(),
        ];
    }

    /**
     * Apply the infocentro role once the user exists.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (User $user): void {
            $user->assignRole(Role::findOrCreate(RoleEnum::Infocentro));
        });
    }

    /**
     * Attach the user to an already existing infocentro.
     */
    public function forInfocentro(Infocentro $infocentro): static
    {
        return $this->state(fn (array $attributes): array => [
            'infocentro_id' => $infocentro->getKey(),
        ]);
    }

    /**
     * Indicate that the user belongs to a deactivated infocentro.
     */
    public function inactivo(): static
    {
        return $this->state(fn (array $attributes): array => [
            'infocentro_id' => Infocentro::factory()->inactivo(),
        ]);
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
