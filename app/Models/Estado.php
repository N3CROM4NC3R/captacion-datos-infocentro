<?php

namespace App\Models;

use Database\Factories\EstadoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre'])]
class Estado extends Model
{
    /** @use HasFactory<EstadoFactory> */
    use HasFactory;

    /**
     * Get the municipios that belong to the estado.
     */
    public function municipios(): HasMany
    {
        return $this->hasMany(Municipio::class);
    }

    /**
     * Get the infocentros located in the estado.
     */
    public function infocentros(): HasMany
    {
        return $this->hasMany(Infocentro::class);
    }
}
