<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * Problemas prioritarios del territorio que la comunidad identifica (I.1).
 *
 * "Otros" activa el campo de texto libre `problemas_otros`.
 */
enum ProblemaTerritorioEnum: string
{
    use ProvidesOptions;

    case Ambientales = 'ambientales';
    case Productivos = 'productivos';
    case Salud = 'salud';
    case Riesgo = 'riesgo';
    case Energia = 'energia';
    case Conectividad = 'conectividad';
    case Formacion = 'formacion';
    case Memoria = 'memoria';
    case Otros = 'otros';

    public function label(): string
    {
        return match ($this) {
            self::Ambientales => 'Ambientales (contaminación, manejo de residuos, agua)',
            self::Productivos => 'Productivos (agricultura, pesca, artesanía, emprendimiento)',
            self::Salud => 'De salud (prevención, nutrición, primeros auxilios)',
            self::Riesgo => 'De riesgo (inundaciones, deslizamientos, incendios)',
            self::Energia => 'De energía (falta de electricidad, necesidad de paneles solares)',
            self::Conectividad => 'De conectividad (falta de internet, señal telefónica)',
            self::Formacion => 'De formación (falta de oportunidades educativas para jóvenes)',
            self::Memoria => 'De memoria (pérdida de saberes tradicionales, historia oral)',
            self::Otros => 'Otros',
        };
    }

    /**
     * Determine whether the selection needs the free-text field.
     */
    public function requiereDetalle(): bool
    {
        return $this === self::Otros;
    }
}
