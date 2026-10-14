<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * Participación del infocentro en las Consultas Populares (E.4).
 */
enum ConsultasPopularesEnum: string
{
    use ProvidesOptions;

    case Activa = 'activa';
    case Limitada = 'limitada';
    case NoParticipa = 'no_participa';

    public function label(): string
    {
        return match ($this) {
            self::Activa => 'Sí, activamente',
            self::Limitada => 'Sí, de manera limitada',
            self::NoParticipa => 'No participa',
        };
    }

    public function requiereDetalle(): bool
    {
        return $this !== self::NoParticipa;
    }
}
