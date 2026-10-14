<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * Forma en que la comunidad participa en la definición de actividades (E.6).
 */
enum ParticipacionComunidadEnum: string
{
    use ProvidesOptions;

    case Asambleas = 'asambleas';
    case Informal = 'informal';
    case NoParticipa = 'no_participa';

    public function label(): string
    {
        return match ($this) {
            self::Asambleas => 'Sí, mediante asambleas periódicas',
            self::Informal => 'Sí, de manera informal',
            self::NoParticipa => 'No participa',
        };
    }
}
