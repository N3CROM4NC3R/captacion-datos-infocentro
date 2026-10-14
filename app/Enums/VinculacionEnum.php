<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * Grado de vinculación con una organización. Reutilizado por la Sala de
 * Autogobierno Comunal (E.1) y los Consejos Comunales (E.2).
 */
enum VinculacionEnum: string
{
    use ProvidesOptions;

    case Activa = 'activa';
    case Pasiva = 'pasiva';
    case Ninguna = 'ninguna';

    public function label(): string
    {
        return match ($this) {
            self::Activa => 'Sí, de manera activa',
            self::Pasiva => 'Sí, de manera pasiva',
            self::Ninguna => 'No está vinculado',
        };
    }

    /**
     * Determine whether the answer needs the follow-up fields.
     */
    public function requiereDetalle(): bool
    {
        return $this !== self::Ninguna;
    }
}
