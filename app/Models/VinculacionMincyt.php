<?php

namespace App\Models;

use App\Enums\EstadoProyectoMincytEnum;
use Database\Factories\VinculacionMincytFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['institucion', 'proyecto', 'estado', 'ultimo_contacto'])]
class VinculacionMincyt extends Model
{
    /** @use HasFactory<VinculacionMincytFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * El pluralizador de Laravel no conoce el español y derivaría
     * "vinculacion_mincyts", así que el nombre se declara explícitamente.
     */
    protected $table = 'vinculaciones_mincyt';

    /**
     * Get the infocentro the institution is linked to.
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
            'estado' => EstadoProyectoMincytEnum::class,
            'ultimo_contacto' => 'date',
        ];
    }
}
