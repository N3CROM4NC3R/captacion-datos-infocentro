<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * Sistemas de energía del infocentro (G.5).
 */
enum TipoEnergiaEnum: string
{
    use ProvidesOptions;

    case Corpoelectrico = 'corpoelectrico';
    case PlantasElectricas = 'plantas_electricas';
    case PanelesSolares = 'paneles_solares';
    case BateriasUps = 'baterias_ups';
    case Otro = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::Corpoelectrico => 'Conexión eléctrica de Corpoeléctrico',
            self::PlantasElectricas => 'Plantas eléctricas',
            self::PanelesSolares => 'Paneles solares',
            self::BateriasUps => 'Baterías / UPS',
            self::Otro => 'Otro',
        };
    }

    /**
     * Determine whether this source has no fixed quantity (a grid connection).
     */
    public function cantidadFija(): bool
    {
        return $this !== self::Corpoelectrico;
    }
}
