<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * Current operational state of the infocentro.
 */
enum OperatividadEnum: string
{
    use ProvidesOptions;

    case Normalidad = 'normalidad';
    case ConLimitaciones = 'con_limitaciones';
    case NoOperativo = 'no_operativo';

    public function label(): string
    {
        return match ($this) {
            self::Normalidad => 'Sí, funciona con normalidad',
            self::ConLimitaciones => 'Sí, con limitaciones',
            self::NoOperativo => 'No operativo',
        };
    }

    /**
     * Determine whether the state needs the "especificar" field.
     */
    public function requiereDetalle(): bool
    {
        return match ($this) {
            self::ConLimitaciones, self::NoOperativo => true,
            default => false,
        };
    }
}
