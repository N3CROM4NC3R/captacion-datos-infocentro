<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * Existencia de la Mesa Técnica de Ciencia y Tecnología Comunal (E.5).
 */
enum MesaTecnicaEnum: string
{
    use ProvidesOptions;

    case Participa = 'participa';
    case NoParticipa = 'no_participa';
    case NoExiste = 'no_existe';

    public function label(): string
    {
        return match ($this) {
            self::Participa => 'Sí, y el infocentro participa',
            self::NoParticipa => 'Sí, pero el infocentro no participa',
            self::NoExiste => 'No existe',
        };
    }
}
