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

    /**
     * Get the label of the free-text field this source asks for.
     *
     * El PDF solo deja un espacio libre en la opción «Otra» del inventario de
     * energía; el resto son casillas de estado.
     */
    public function etiquetaDetalle(): ?string
    {
        return match ($this) {
            self::Otro => 'Especificar',
            default => null,
        };
    }
}
