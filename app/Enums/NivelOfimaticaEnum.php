<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * Nivel de ofimática (F.5).
 */
enum NivelOfimaticaEnum: string
{
    use ProvidesOptions;

    case Basico = 'basico';
    case Intermedio = 'intermedio';
    case Avanzado = 'avanzado';

    public function label(): string
    {
        return match ($this) {
            self::Basico => 'Básico',
            self::Intermedio => 'Intermedio',
            self::Avanzado => 'Avanzado',
        };
    }
}
