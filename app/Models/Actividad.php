<?php

namespace App\Models;

use App\Enums\FrecuenciaEnum;
use App\Enums\NivelOfimaticaEnum;
use App\Enums\TipoActividadEnum;
use Database\Factories\ActividadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tipo', 'activa', 'frecuencia', 'participantes', 'detalle', 'opcion_si_no', 'opcion_nivel'])]
class Actividad extends Model
{
    /** @use HasFactory<ActividadFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * El pluralizador de Laravel no conoce el español y derivaría
     * "actividads", así que el nombre se declara explícitamente.
     */
    protected $table = 'actividades';

    /**
     * Get the infocentro the activity belongs to.
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
            'tipo' => TipoActividadEnum::class,
            'activa' => 'boolean',
            'frecuencia' => FrecuenciaEnum::class,
            'participantes' => 'integer',
            'opcion_si_no' => 'boolean',
            'opcion_nivel' => NivelOfimaticaEnum::class,
        ];
    }
}
