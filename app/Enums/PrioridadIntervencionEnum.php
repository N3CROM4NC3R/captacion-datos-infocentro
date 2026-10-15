<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * Prioridad de intervención asignada al infocentro (J.3).
 */
enum PrioridadIntervencionEnum: string
{
    use ProvidesOptions;

    case Inmediata = 'inmediata';
    case CortoPlazo = 'corto_plazo';
    case MedianoPlazo = 'mediano_plazo';

    public function label(): string
    {
        return match ($this) {
            self::Inmediata => 'Inmediata',
            self::CortoPlazo => 'Corto plazo (3-6 meses)',
            self::MedianoPlazo => 'Mediano plazo (6-12 meses)',
        };
    }
}
