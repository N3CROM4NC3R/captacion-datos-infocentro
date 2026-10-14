<?php

namespace App\Models;

use Database\Factories\MunicipioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre'])]
class Municipio extends Model
{
    /** @use HasFactory<MunicipioFactory> */
    use HasFactory;

    /**
     * Get the estado the municipio belongs to.
     */
    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class);
    }

    /**
     * Get the parroquias that belong to the municipio.
     */
    public function parroquias(): HasMany
    {
        return $this->hasMany(Parroquia::class);
    }

    /**
     * Get the infocentros located in the municipio.
     */
    public function infocentros(): HasMany
    {
        return $this->hasMany(Infocentro::class);
    }
}
