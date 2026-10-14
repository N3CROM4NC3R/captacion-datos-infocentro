<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * Whether the facilitators receive continuous training.
 */
enum FormacionContinuaEnum: string
{
    use ProvidesOptions;

    case Periodica = 'periodica';
    case Ocasional = 'ocasional';
    case No = 'no';

    public function label(): string
    {
        return match ($this) {
            self::Periodica => 'Sí, periódica',
            self::Ocasional => 'Sí, ocasional',
            self::No => 'No',
        };
    }
}
