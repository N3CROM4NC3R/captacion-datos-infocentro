<?php

namespace App\Models;

use App\Enums\DocumentoAnexoEnum;
use Database\Factories\DocumentoAnexoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Un PDF anexo al diagnóstico (sección J).
 *
 * El archivo vive en el disco privado, no en la base de datos: aquí solo queda
 * la ruta y los datos necesarios para reconstruir el enlace de descarga.
 */
#[Fillable(['tipo', 'etiqueta', 'ruta', 'nombre_original', 'mime', 'tamano'])]
class DocumentoAnexo extends Model
{
    /** @use HasFactory<DocumentoAnexoFactory> */
    use HasFactory;

    /**
     * La ruta al archivo dentro del disco configurado.
     */
    protected $table = 'documentos_anexos';

    /**
     * Get the infocentro the document belongs to.
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
            'tipo' => DocumentoAnexoEnum::class,
            'tamano' => 'integer',
        ];
    }

    /**
     * Get the file's name as it should be shown, including the «Otros» label.
     */
    public function nombreVisible(): string
    {
        return $this->etiqueta === null
            ? $this->nombre_original
            : $this->etiqueta.' — '.$this->nombre_original;
    }

    /**
     * Get the file's size formatted for display.
     */
    public function tamanoLegible(): string
    {
        if ($this->tamano === null) {
            return '';
        }

        return $this->tamano >= 1_048_576
            ? number_format($this->tamano / 1_048_576, 1, ',', '.').' MB'
            : number_format($this->tamano / 1024, 0, ',', '.').' KB';
    }

    /**
     * Determine whether the file is still present on disk.
     */
    public function existeEnDisco(): bool
    {
        return Storage::disk(config('filesystems.default'))->exists($this->ruta);
    }
}
