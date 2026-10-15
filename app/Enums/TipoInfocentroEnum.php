<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * Clasificación del infocentro (J.2).
 *
 * No se elige: se deduce del total de J.1. Los tres rangos del PDF son
 * contiguos y completos, así que todo total entre 0 y 21 cae en exactamente
 * uno.
 */
enum TipoInfocentroEnum: string
{
    use ProvidesOptions;

    case A = 'a';
    case B = 'b';
    case C = 'c';

    public function label(): string
    {
        return match ($this) {
            self::A => 'Tipo A — Núcleo piloto',
            self::B => 'Tipo B — En consolidación',
            self::C => 'Tipo C — Por recuperar',
        };
    }

    /**
     * Get the paragraph describing this kind of infocentro.
     */
    public function descripcion(): string
    {
        return match ($this) {
            self::A => 'Infocentro operativo, con conectividad, facilitadores formados, equipamiento suficiente y articulación comunitaria activa.',
            self::B => 'Infocentro parcialmente operativo, con limitaciones en conectividad, equipamiento o facilitadores, pero con potencial comunitario.',
            self::C => 'Infocentro inoperativo o con graves limitaciones.',
        };
    }

    /**
     * Get the recommended course of action for this kind of infocentro.
     */
    public function intervencion(): string
    {
        return match ($this) {
            self::A => 'Oferta completa del Programa ENLACE.',
            self::B => 'Priorizar formación para adultos mayores, apoyo a salas de autogobierno y contenidos científicos offline.',
            self::C => 'Espacio de encuentro presencial y asambleas de ciencia ciudadana; articulación con CANTV, Corpoelec y el Poder Popular para rehabilitación física y tecnológica.',
        };
    }

    /**
     * Get the score range this type covers, as the PDF prints it.
     */
    public function rango(): string
    {
        return match ($this) {
            self::A => '15-21 puntos',
            self::B => '8-14 puntos',
            self::C => '0-7 puntos',
        };
    }

    /**
     * Get the lowest score that falls into this type.
     */
    public function desde(): int
    {
        return match ($this) {
            self::A => 15,
            self::B => 8,
            self::C => 0,
        };
    }

    /**
     * Determine the type an infocentro falls into from its total score.
     */
    public static function desdePuntuacion(int $total): self
    {
        return match (true) {
            $total >= self::A->desde() => self::A,
            $total >= self::B->desde() => self::B,
            default => self::C,
        };
    }
}
