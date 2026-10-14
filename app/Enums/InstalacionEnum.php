<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * Checklist of installation states, all of which can apply at once.
 */
enum InstalacionEnum: string
{
    use ProvidesOptions;

    case TechoBueno = 'techo_bueno';
    case TechoDañado = 'techo_danado';
    case ParedesBuenas = 'paredes_buenas';
    case ParedesDañadas = 'paredes_danadas';
    case PisosBuenos = 'pisos_buenos';
    case PisosDañados = 'pisos_danados';
    case IluminacionFuncional = 'iluminacion_funcional';
    case IluminacionDeficiente = 'iluminacion_deficiente';
    case VentilacionAdecuada = 'ventilacion_adecuada';
    case BañosFuncionales = 'banos_funcionales';
    case BañosFueraServicio = 'banos_fuera_servicio';
    case AccesoDiscapacidad = 'acceso_discapacidad';

    public function label(): string
    {
        return match ($this) {
            self::TechoBueno => 'Techo en buen estado',
            self::TechoDañado => 'Techo con filtraciones / daños',
            self::ParedesBuenas => 'Paredes en buen estado',
            self::ParedesDañadas => 'Paredes con humedad / grietas',
            self::PisosBuenos => 'Pisos en buen estado',
            self::PisosDañados => 'Pisos dañados',
            self::IluminacionFuncional => 'Iluminación eléctrica funcional',
            self::IluminacionDeficiente => 'Iluminación eléctrica deficiente',
            self::VentilacionAdecuada => 'Ventilación adecuada',
            self::BañosFuncionales => 'Baños funcionales',
            self::BañosFueraServicio => 'Baños fuera de servicio',
            self::AccesoDiscapacidad => 'Acceso para personas con discapacidad',
        };
    }
}
