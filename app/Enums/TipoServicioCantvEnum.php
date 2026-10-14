<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * Tipo de servicio que CANTV le presta al infocentro.
 *
 * El PDF original deja el tipo en un renglón libre ("fibra óptica / ABA /
 * otro"). Se convierte en una lista cerrada porque las tres modalidades cubren
 * lo que el programa registra; el que no califica en ninguna ya se anota en las
 * observaciones de conectividad.
 */
enum TipoServicioCantvEnum: string
{
    use ProvidesOptions;

    case FibraOptica = 'fibra_optica';
    case Aba = 'aba';
    case Satelital = 'satelital';

    public function label(): string
    {
        return match ($this) {
            self::FibraOptica => 'Fibra óptica',
            self::Aba => 'ABA',
            self::Satelital => 'Satelital',
        };
    }
}
