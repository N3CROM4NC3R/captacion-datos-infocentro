<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * Whether the current schedule meets the community's needs.
 */
enum SuficienciaHorarioEnum: string
{
    use ProvidesOptions;

    case Si = 'si';
    case No = 'no';
    case Parcialmente = 'parcialmente';

    public function label(): string
    {
        return match ($this) {
            self::Si => 'Sí',
            self::No => 'No',
            self::Parcialmente => 'Parcialmente',
        };
    }

    /**
     * Determine whether the answer needs the "¿por qué?" field.
     */
    public function requiereMotivo(): bool
    {
        return match ($this) {
            self::No, self::Parcialmente => true,
            default => false,
        };
    }
}
