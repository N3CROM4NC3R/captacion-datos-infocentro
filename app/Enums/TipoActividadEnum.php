<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * Actividades del infocentro (sección F).
 *
 * "Otras" representa F.9, donde el nombre libre de la actividad va en el
 * campo detalle de la fila.
 */
enum TipoActividadEnum: string
{
    use ProvidesOptions;

    case Robotica = 'robotica';
    case PequenosIngenieros = 'pequenos_ingenieros';
    case CienciasBasicas = 'ciencias_basicas';
    case Astronomia = 'astronomia';
    case Ofimatica = 'ofimatica';
    case InteligenciaArtificial = 'inteligencia_artificial';
    case AtencionAlSoberano = 'atencion_al_soberano';
    case AlfabetizacionDigital = 'alfabetizacion_digital';
    case Otras = 'otras';

    public function label(): string
    {
        return match ($this) {
            self::Robotica => 'Unidad de robótica',
            self::PequenosIngenieros => 'Pequeños Ingenieros',
            self::CienciasBasicas => 'Ciencias Básicas',
            self::Astronomia => 'Astronomía',
            self::Ofimatica => 'Ofimática',
            self::InteligenciaArtificial => 'Inteligencia Artificial',
            self::AtencionAlSoberano => 'Atención al Soberano',
            self::AlfabetizacionDigital => 'Alfabetización Digital para Adultos Mayores',
            self::Otras => 'Otras actividades',
        };
    }

    /**
     * Get the label of the variable field this activity asks for.
     */
    public function etiquetaDetalle(): string
    {
        return match ($this) {
            self::Robotica => 'Software / hardware utilizado',
            self::PequenosIngenieros => 'Temas abordados',
            self::CienciasBasicas => 'Áreas específicas',
            self::InteligenciaArtificial => '¿Qué herramientas?',
            self::AlfabetizacionDigital => 'Temas',
            self::Otras => 'Nombre de la actividad',
            default => 'Detalle',
        };
    }

    /**
     * Determine whether this activity asks "¿Cuenta con telescopio?".
     */
    public function tieneOpcionBinaria(): bool
    {
        return $this === self::Astronomia;
    }

    /**
     * Get the label of that yes/no option.
     */
    public function etiquetaOpcionBinaria(): string
    {
        return match ($this) {
            self::Astronomia => '¿Cuenta con telescopio?',
            self::AtencionAlSoberano => '¿Atiende adultos mayores?',
            default => '',
        };
    }

    /**
     * Determine whether this activity asks a "nivel" question.
     */
    public function tieneNivel(): bool
    {
        return $this === self::Ofimatica;
    }
}
