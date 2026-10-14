<?php

namespace App\Models;

use App\Enums\EstadoEquipoEnum;
use App\Enums\TipoEnergiaEnum;
use Database\Factories\EnergiaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tipo', 'cantidad', 'estado', 'detalle', 'observaciones'])]
class Energia extends Model
{
    /** @use HasFactory<EnergiaFactory> */
    use HasFactory;

    /**
     * Get the infocentro the energy source belongs to.
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
            'tipo' => TipoEnergiaEnum::class,
            'cantidad' => 'integer',
            'estado' => EstadoEquipoEnum::class,
        ];
    }
}
