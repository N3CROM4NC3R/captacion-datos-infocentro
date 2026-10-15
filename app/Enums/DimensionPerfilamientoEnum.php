<?php

namespace App\Enums;

use App\Enums\Concerns\ProvidesOptions;

/**
 * Dimensiones que puntúa el diagnosticador en J.1.
 *
 * Cada una vale de 0 a 3 y la escala es la que el formulario original describe
 * debajo de la línea en blanco: se conserva literal para que quien puntúa
 * tenga el criterio a la vista al elegir, no en una nota aparte.
 */
enum DimensionPerfilamientoEnum: string
{
    use ProvidesOptions;

    /**
     * Puntuar más que esto ya no es una dimensión, es un error de dedo.
     */
    public const PUNTUACION_MAXIMA = 3;

    /**
     * Las siete dimensiones suman este total, el mismo que pide el PDF.
     */
    public const TOTAL_MAXIMO = 21;

    case Conectividad = 'conectividad';
    case Operatividad = 'operatividad';
    case Facilitadores = 'facilitadores';
    case Equipamiento = 'equipamiento';
    case ArticulacionComunitaria = 'articulacion_comunitaria';
    case OfertaFormativa = 'oferta_formativa';
    case VinculacionMincyt = 'vinculacion_mincyt';

    public function label(): string
    {
        return match ($this) {
            self::Conectividad => 'Conectividad',
            self::Operatividad => 'Operatividad',
            self::Facilitadores => 'Facilitadores',
            self::Equipamiento => 'Equipamiento',
            self::ArticulacionComunitaria => 'Articulación comunitaria',
            self::OfertaFormativa => 'Oferta formativa',
            self::VinculacionMincyt => 'Vinculación con Mincyt',
        };
    }

    /**
     * Get what each score means for this dimension, from 3 down to 0.
     *
     * @return array<int, string>
     */
    public function escala(): array
    {
        return match ($this) {
            self::Conectividad => [
                3 => 'Fibra óptica, estable, gratuito',
                2 => 'Internet intermitente',
                1 => 'Solo datos móviles',
                0 => 'Sin internet',
            ],
            self::Operatividad => [
                3 => 'Funciona con horario extendido',
                2 => 'Funciona con horario limitado',
                1 => 'Funciona esporádicamente',
                0 => 'Cerrado',
            ],
            self::Facilitadores => [
                3 => '2+ facilitadores con formación en ciencia/TIC',
                2 => '1 facilitador con formación',
                1 => 'Facilitador sin formación específica',
                0 => 'Sin facilitador',
            ],
            self::Equipamiento => [
                3 => 'Computadoras funcionales + equipamiento científico',
                2 => 'Computadoras funcionales',
                1 => 'Computadoras parciales',
                0 => 'Sin equipamiento',
            ],
            self::ArticulacionComunitaria => [
                3 => 'Vinculado activamente con sala de autogobierno, consejos comunales y consultas populares',
                2 => 'Vinculado con algunos',
                1 => 'Vinculación pasiva',
                0 => 'Sin vinculación',
            ],
            self::OfertaFormativa => [
                3 => 'Ofrece 4+ tipos de actividades con regularidad',
                2 => 'Ofrece 2-3 actividades',
                1 => 'Ofrece 1 actividad',
                0 => 'Sin actividades formativas',
            ],
            self::VinculacionMincyt => [
                3 => 'Vinculado activo con Fundacite y programas del Mincyt',
                2 => 'Vinculación esporádica',
                1 => 'Conoce pero no vincula',
                0 => 'Sin conocimiento',
            ],
        };
    }

    /**
     * Get the Alpine property that holds this dimension's score.
     *
     * Cada dimensión se enlaza a una propiedad y no a una clave de objeto:
     * `x-model` se compila como destino de una asignación, así que
     * `puntuaciones['conectividad']` no es un destino válido y Alpine lanza un
     * SyntaxError que deja la sección entera sin pintar. El nombre se define
     * aquí para que la vista y el componente Alpine no puedan descuadrarse.
     */
    public function propiedadAlpine(): string
    {
        return 'puntuacion'.ucfirst($this->name);
    }

    /**
     * Get the scores of this dimension as select options, legend included.
     *
     * @return array<int, string>
     */
    public function opciones(): array
    {
        $opciones = [];

        // La escala va de 3 a 0, así que se recorre tal cual: el número es la
        // clave y no un índice que pueda desfasarse.
        foreach ($this->escala() as $puntos => $descripcion) {
            $opciones[$puntos] = $puntos === 0
                ? $descripcion
                : $puntos.' puntos — '.$descripcion;
        }

        return $opciones;
    }
}
