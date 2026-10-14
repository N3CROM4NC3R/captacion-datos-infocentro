<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * Whether the infocentro currently has internet service.
 */
enum ServicioInternetEnum: string
{
    use ProvidesOptions;

    case SiOperativo = 'si_operativo';
    case SiIntermitente = 'si_intermitente';
    case No = 'no';

    public function label(): string
    {
        return match ($this) {
            self::SiOperativo => 'Sí, operativo',
            self::SiIntermitente => 'Sí, intermitente',
            self::No => 'No',
        };
    }
}
