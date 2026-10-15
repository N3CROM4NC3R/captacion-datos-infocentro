<?php

namespace App\Models;

use App\Enums\EstadoEquipoEnum;
use App\Enums\FormatoEquipoEnum;
use App\Enums\TipoEquipoEnum;
use Database\Factories\EquipoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tipo', 'cantidad', 'funcionando', 'fuera_servicio', 'estado', 'detalle', 'formato', 'observaciones'])]
class Equipo extends Model
{
    /** @use HasFactory<EquipoFactory> */
    use HasFactory;

    /**
     * Get the infocentro the equipment belongs to.
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
            'tipo' => TipoEquipoEnum::class,
            'cantidad' => 'integer',
            'funcionando' => 'integer',
            'fuera_servicio' => 'integer',
            'estado' => EstadoEquipoEnum::class,
            'formato' => FormatoEquipoEnum::class,
        ];
    }
}
