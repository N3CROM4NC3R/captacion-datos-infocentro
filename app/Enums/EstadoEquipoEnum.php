<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * Estado del equipamiento y de los sistemas de energía (sección G).
 */
enum EstadoEquipoEnum: string
{
    use ProvidesOptions;

    case Optimo = 'optimo';
    case Bueno = 'bueno';
    case Regular = 'regular';
    case Deficiente = 'deficiente';
    case NoTiene = 'no_tiene';

    public function label(): string
    {
        return match ($this) {
            self::Optimo => 'Óptimo',
            self::Bueno => 'Bueno',
            self::Regular => 'Regular',
            self::Deficiente => 'Deficiente',
            self::NoTiene => 'No tiene',
        };
    }

    /**
     * Determine whether this state implies a quantity of zero.
     */
    public function implicaCantidadCero(): bool
    {
        return $this === self::NoTiene;
    }
}
