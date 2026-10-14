<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * Suficiencia del equipamiento frente a la demanda actual (G.7).
 */
enum SuficienciaEquipamientoEnum: string
{
    use ProvidesOptions;

    case Sobra = 'sobra';
    case Justo = 'justo';
    case Falta = 'falta';

    public function label(): string
    {
        return match ($this) {
            self::Sobra => 'Sí, sobra',
            self::Justo => 'Sí, justo',
            self::Falta => 'No, falta',
        };
    }

    /**
     * Determine whether the answer needs the "¿qué falta?" field.
     */
    public function requiereFaltante(): bool
    {
        return $this === self::Falta;
    }
}
