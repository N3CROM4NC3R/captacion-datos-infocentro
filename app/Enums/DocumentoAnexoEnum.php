<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * Documentos anexos que el infocentro adjunta al diagnóstico (J).
 *
 * El formulario original los marca con casillas («marque los que adjunta»);
 * aquí cada casilla es un único archivo PDF.
 */
enum DocumentoAnexoEnum: string
{
    use ProvidesOptions;

    /**
     * Tope por archivo, en kilobytes.
     *
     * Los seis archivos viajan en el mismo POST que el resto de la ficha, así
     * que `post_max_size` tiene que aguantar seis veces este valor.
     */
    public const TAMANO_MAXIMO_KB = 10240;

    case FotosEspacio = 'fotos_espacio';
    case FotosEquipamiento = 'fotos_equipamiento';
    case ConvenioSac = 'convenio_sac';
    case PlanActividades = 'plan_actividades';
    case ListadoParticipantes = 'listado_participantes';
    case Otros = 'otros';

    public function label(): string
    {
        return match ($this) {
            self::FotosEspacio => 'Fotografías del espacio físico',
            self::FotosEquipamiento => 'Fotografías del equipamiento',
            self::ConvenioSac => 'Convenio de articulación con Sala de Autogobierno (si aplica)',
            self::PlanActividades => 'Plan de actividades del infocentro (si existe)',
            self::ListadoParticipantes => 'Listado de participantes en actividades recientes',
            self::Otros => 'Otros',
        };
    }

    /**
     * Get the label of the free-text field this document asks for.
     *
     * El PDF solo deja una línea libre en la última opción; el resto son
     * casillas sin nada que precisar.
     */
    public function etiquetaDetalle(): ?string
    {
        return match ($this) {
            self::Otros => 'Especificar',
            default => null,
        };
    }
}
