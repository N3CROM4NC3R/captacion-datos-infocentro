<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * Estado del proyecto con una institución del MINCYT (H.2).
 */
enum EstadoProyectoMincytEnum: string
{
    use ProvidesOptions;

    case Activo = 'activo';
    case Concluido = 'concluido';
    case Inactivo = 'inactivo';

    public function label(): string
    {
        return match ($this) {
            self::Activo => 'Activo',
            self::Concluido => 'Concluido',
            self::Inactivo => 'Inactivo',
        };
    }
}
