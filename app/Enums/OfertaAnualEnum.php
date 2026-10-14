<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * Oferta formativa planificada anualmente (F.10).
 */
enum OfertaAnualEnum: string
{
    use ProvidesOptions;

    case Si = 'si';
    case No = 'no';
    case Parcial = 'parcial';

    public function label(): string
    {
        return match ($this) {
            self::Si => 'Sí',
            self::No => 'No',
            self::Parcial => 'Parcial',
        };
    }

    /**
     * Determine whether the answer needs the "¿quién la define?" field.
     */
    public function requiereResponsable(): bool
    {
        return $this !== self::No;
    }
}
