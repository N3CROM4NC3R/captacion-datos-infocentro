<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * Tipos de equipamiento inventariado en la sección G.
 */
enum TipoEquipoEnum: string
{
    use ProvidesOptions;

    case Computadoras = 'computadoras';
    case TelescopiosPortatiles = 'telescopios_portatiles';
    case KitsRobotica = 'kits_robotica';
    case ModemRouter = 'modem_router';
    case Otros = 'otros';

    public function label(): string
    {
        return match ($this) {
            self::Computadoras => 'Computadoras',
            self::TelescopiosPortatiles => 'Telescopios portátiles',
            self::KitsRobotica => 'Kits de robótica',
            self::ModemRouter => 'Modem / Router',
            self::Otros => 'Otros equipos',
        };
    }

    /**
     * Determine whether this equipment tracks working vs out-of-service units.
     */
    public function tieneDesglose(): bool
    {
        return $this === self::Computadoras;
    }

    /**
     * Determine whether this equipment asks if it is desktop or laptop units.
     */
    public function tieneFormato(): bool
    {
        return $this === self::Computadoras;
    }

    /**
     * Get the label of the free-text field this equipment asks for.
     *
     * Devuelve `null` para los tipos que no la responden: el PDF solo la pide
     * para computadoras (sistema operativo), kits de robótica (tipo de kits) y
     * otros equipos (especificar). Devolver la etiqueta o `null` en el mismo
     * método evita que exista un campo sin rótulo o al revés.
     */
    public function etiquetaDetalle(): ?string
    {
        return match ($this) {
            self::Computadoras => '¿Con qué sistema operativo?',
            self::KitsRobotica => 'Tipo de kits',
            self::Otros => 'Especificar',
            self::TelescopiosPortatiles, self::ModemRouter => null,
        };
    }

    /**
     * Determine whether "no tiene" is a valid state for this equipment.
     */
    public function admiteNoTiene(): bool
    {
        return $this !== self::Computadoras;
    }
}
