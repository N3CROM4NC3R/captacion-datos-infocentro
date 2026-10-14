<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * Grado de interés o demanda de un grupo del territorio.
 *
 * Compartido por I.3 (jóvenes interesados en formarse) e I.4 (adultos mayores
 * que demanden alfabetización digital), que el PDF plantea con las mismas
 * cuatro opciones.
 */
enum InteresDemandaEnum: string
{
    use ProvidesOptions;

    case SiMuchos = 'si_muchos';
    case SiAlgunos = 'si_algunos';
    case Pocos = 'pocos';
    case No = 'no';

    public function label(): string
    {
        return match ($this) {
            self::SiMuchos => 'Sí, muchos',
            self::SiAlgunos => 'Sí, algunos',
            self::Pocos => 'Pocos',
            self::No => 'No',
        };
    }
}
