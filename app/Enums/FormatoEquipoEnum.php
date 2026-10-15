<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * Formato físico de los equipos del inventario (pregunta G.1).
 *
 * Solo tiene sentido para las computadoras; el resto de tipos de
 * equipamiento no la responden.
 */
enum FormatoEquipoEnum: string
{
    use ProvidesOptions;

    case Escritorio = 'escritorio';
    case Portatiles = 'portatiles';
    case Ambas = 'ambas';

    public function label(): string
    {
        return match ($this) {
            self::Escritorio => 'De escritorio',
            self::Portatiles => 'Portátiles',
            self::Ambas => 'De escritorio y portátiles',
        };
    }
}
