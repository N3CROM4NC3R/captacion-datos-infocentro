<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * Days of the week the diagnosis form covers, Monday through Sunday.
 */
enum DiaSemanaEnum: string
{
    use ProvidesOptions;

    case Lunes = 'lunes';
    case Martes = 'martes';
    case Miercoles = 'miercoles';
    case Jueves = 'jueves';
    case Viernes = 'viernes';
    case Sabado = 'sabado';
    case Domingo = 'domingo';

    public function label(): string
    {
        return match ($this) {
            self::Lunes => 'Lunes',
            self::Martes => 'Martes',
            self::Miercoles => 'Miércoles',
            self::Jueves => 'Jueves',
            self::Viernes => 'Viernes',
            self::Sabado => 'Sábado',
            self::Domingo => 'Domingo',
        };
    }
}
