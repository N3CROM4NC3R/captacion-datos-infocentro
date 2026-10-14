<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * Academic levels a facilitator can hold, as listed in the diagnosis form.
 */
enum NivelAcademicoEnum: string
{
    use ProvidesOptions;

    case Bachiller = 'bachiller';
    case Tsu = 'tsu';
    case Universitario = 'universitario';
    case Postgrado = 'postgrado';

    public function label(): string
    {
        return match ($this) {
            self::Bachiller => 'Bachiller',
            self::Tsu => 'TSU',
            self::Universitario => 'Universitario',
            self::Postgrado => 'Postgrado',
        };
    }
}
