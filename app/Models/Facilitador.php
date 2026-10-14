<?php

namespace App\Models;

use App\Enums\NivelAcademicoEnum;
use Database\Factories\FacilitadorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'nombre',
    'nivel_academico',
    'titulo_carrera',
    'anios_experiencia',
    'formacion_tic',
    'formacion_tic_detalle',
    'formacion_ciencias',
    'formacion_ciencias_area',
    'horas_semanales',
])]
class Facilitador extends Model
{
    /** @use HasFactory<FacilitadorFactory> */
    use HasFactory;

    protected $table = 'facilitadores';

    /**
     * Get the infocentro the facilitator belongs to.
     */
    public function infocentro(): BelongsTo
    {
        return $this->belongsTo(Infocentro::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nivel_academico' => NivelAcademicoEnum::class,
            'anios_experiencia' => 'integer',
            'formacion_tic' => 'boolean',
            'formacion_ciencias' => 'boolean',
            'horas_semanales' => 'integer',
        ];
    }
}
