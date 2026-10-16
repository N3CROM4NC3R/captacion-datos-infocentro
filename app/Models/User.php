<?php

namespace App\Models;

use App\Enums\RoleEnum;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'username', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the infocentro the user belongs to. Null for the general administrator.
     */
    public function infocentro(): BelongsTo
    {
        return $this->belongsTo(Infocentro::class);
    }

    /**
     * Whether this account can enter the administration panel.
     *
     * Las tres puertas que deciden esto —el middleware del grupo `/admin`, la
     * redirección de `/dashboard` y la del login— preguntan aquí. Si cada una
     * preguntara por su cuenta con `hasRole`, arreglar el rol nuevo en dos y
     * olvidar el tercero dejaría al administrador técnico entrando y saliendo
     * despedido hacia el formulario de ficha, que no puede abrir porque no
     * tiene infocentro.
     */
    public function esAdministrador(): bool
    {
        return $this->hasAnyRole(RoleEnum::delPanel());
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
