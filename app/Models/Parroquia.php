<?php

namespace App\Models;

use Database\Factories\ParroquiaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre'])]
class Parroquia extends Model
{
    /** @use HasFactory<ParroquiaFactory> */
    use HasFactory;

    /**
     * Get the municipio the parroquia belongs to.
     */
    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class);
    }

    /**
     * Get the infocentros located in the parroquia.
     */
    public function infocentros(): HasMany
    {
        return $this->hasMany(Infocentro::class);
    }
}
