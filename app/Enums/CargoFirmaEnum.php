<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * Personas que firman eldiagnóstico al cierre de la ficha.
 *
 * El bloque FIRMAS del formulario original fija siempre los mismos cuatro
 * cargos, así que no es una lista variable como la de facilitadores o equipos:
 * se guarda como columna JSON en `infocentros.firmas`.
 */
enum CargoFirmaEnum: string
{
    use ProvidesOptions;

    case Diagnosticador = 'diagnosticador';
    case Facilitador = 'facilitador';
    case VoceroSac = 'vocero_sac';
    case ConsejoComunal = 'consejo_comunal';

    public function label(): string
    {
        return match ($this) {
            self::Diagnosticador => 'Diagnosticador',
            self::Facilitador => 'Facilitador/a del Infocentro',
            self::VoceroSac => 'Vocero/a Sala de Autogobierno Comunal',
            self::ConsejoComunal => 'Representante Consejo Comunal',
        };
    }

    /**
     * Get the label of the fields each person fills in.
     *
     * El original imprime una línea para el nombre, otra para la firma y otra
     * para la fecha, en la misma fila.
     *
     * @return array<string, string>
     */
    public function campos(): array
    {
        return [
            'nombre' => __('Nombre'),
            'firma' => __('Firma'),
            'fecha' => __('Fecha'),
        ];
    }
}
