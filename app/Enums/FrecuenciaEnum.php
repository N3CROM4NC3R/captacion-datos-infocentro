<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * Frecuencia con la que se desarrolla una actividad (sección F).
 */
enum FrecuenciaEnum: string
{
    use ProvidesOptions;

    case Diaria = 'diaria';
    case Semanal = 'semanal';
    case Quincenal = 'quincenal';
    case Mensual = 'mensual';
    case Esporadica = 'esporadica';

    public function label(): string
    {
        return match ($this) {
            self::Diaria => 'Diaria',
            self::Semanal => 'Semanal',
            self::Quincenal => 'Quincenal',
            self::Mensual => 'Mensual',
            self::Esporadica => 'Esporádica',
        };
    }
}
