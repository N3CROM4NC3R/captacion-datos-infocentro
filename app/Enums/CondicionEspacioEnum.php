<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * Condition of the physical space, as asked by the diagnosis form.
 */
enum CondicionEspacioEnum: string
{
    use ProvidesOptions;

    case Adecuado = 'adecuado';
    case MantenimientoMenor = 'mantenimiento_menor';
    case Rehabilitacion = 'rehabilitacion';

    public function label(): string
    {
        return match ($this) {
            self::Adecuado => 'Sí',
            self::MantenimientoMenor => 'Requiere mantenimiento menor',
            self::Rehabilitacion => 'Requiere rehabilitación',
        };
    }
}
