<?php

use App\Http\Requests\Infocentro\GuardarDiagnosticoRequest;
use App\Models\Infocentro;
use Illuminate\Support\Facades\Schema;

/**
 * Campos que la ficha muestra pero no debe escribir.
 *
 * El código identifica el registro y no se edita desde ningún formulario: lo
 * asigna el archivo maestro y por eso queda fuera del alcance de quien llena la
 * ficha, tanto en el panel de administración como aquí.
 */
$camposSoloAdmin = ['codigo'];

test('todo campo que la ficha edita tiene regla de validación', function () use ($camposSoloAdmin) {
    // `puntuacion_total`, `tipo_infocentro` y las dos fechas de la ficha no los
    // escribe nadie: se deducen en cada guardado, así que no tienen por qué
    // tener regla propia.
    $derivados = [
        'puntuacion_total', 'tipo_infocentro',
        'diagnostico_guardado_at', 'diagnostico_actualizado_at',
    ];

    $editables = collect((new Infocentro)->getFillable())
        ->reject(fn (string $campo): bool => in_array($campo, $camposSoloAdmin, true)
            || in_array($campo, $derivados, true));

    $validados = collect(array_keys((new GuardarDiagnosticoRequest)->rules()));

    // Un campo fillable sin regla no llega a validated() y por lo tanto nunca
    // se guarda: la pérdida es silenciosa, sin error ni aviso.
    expect($editables->diff($validados)->values()->all())->toBe([]);
});

test('la ficha no puede escribir los campos que administra el panel', function () use ($camposSoloAdmin) {
    $validados = collect(array_keys((new GuardarDiagnosticoRequest)->rules()));

    expect($validados->intersect($camposSoloAdmin)->values()->all())->toBe([]);
});

test('cada regla de la ficha apunta a una columna real', function () {
    $columnas = Schema::getColumnListing('infocentros');

    // Contenedores del payload anidado, no columnas de infocentros. Cada uno
    // tiene su propia tabla (o se descarta) en DiagnosticoController::update().
    $noColumnas = [
        'facilitadores', 'actividades', 'equipos', 'energias', 'vinculaciones',
        'documentos', 'documentos_etiqueta', 'documentos_eliminar',
    ];

    $desconocidas = collect(array_keys((new GuardarDiagnosticoRequest)->rules()))
        ->reject(fn (string $campo): bool => str_contains($campo, '.')
            || in_array($campo, $columnas, true)
            || in_array($campo, $noColumnas, true));

    expect($desconocidas->values()->all())->toBe([]);
});
