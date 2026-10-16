<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

enum RoleEnum: string
{
    use ProvidesOptions;

    case Administrador = 'administrador';
    case AdministradorTecnico = 'administrador_tecnico';
    case Infocentro = 'infocentro';

    /**
     * The roles that let someone into the administration panel.
     *
     * Vive aquí y no repartido en un `hasRole` por cada puerta —el middleware
     * del grupo, la redirección de `/dashboard` y la del login—, porque son tres
     * sitios y basta con arreglar uno y olvidar los otros dos para que un
     * administrador técnico entre y lo echen al formulario de ficha.
     *
     * @return list<string>
     */
    public static function delPanel(): array
    {
        return [self::Administrador->value, self::AdministradorTecnico->value];
    }

    /**
     * Get the human readable label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Administrador => 'Administrador general',
            self::AdministradorTecnico => 'Administrador técnico',
            self::Infocentro => 'Infocentro',
        };
    }
}
