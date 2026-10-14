<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

enum RoleEnum: string
{
    use ProvidesOptions;

    case Administrador = 'administrador';
    case Infocentro = 'infocentro';

    /**
     * Get the human readable label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Administrador => 'Administrador',
            self::Infocentro => 'Infocentro',
        };
    }
}
