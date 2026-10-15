<?php

use App\Enums\EstadoProyectoMincytEnum;
use App\Enums\InteresDemandaEnum;
use App\Enums\NivelAcademicoEnum;
use App\Models\Estado;
use App\Models\Infocentro;
use Database\Factories\FacilitadorFactory;
use Database\Factories\InfocentroUserFactory;
use Database\Factories\VinculacionMincytFactory;

/**
 * Cada columna de `infocentros` casteada a un enum respaldado.
 *
 * Se lee del propio modelo para que la lista no quede obsoleta: agregar un enum
 * nuevo lo incorpora a la verificación sin tocar este archivo.
 *
 * @return array<string, class-string<BackedEnum>>
 */
function columnasConEnum(): array
{
    // `tipo_infocentro` es un enum respaldado, pero no lo elige nadie: se
    // deduce del total de J.1 y se pinta como Tarjeta, no como desplegable.
    $derivadas = ['tipo_infocentro'];

    $columnas = [];

    foreach ((new Infocentro)->getCasts() as $columna => $cast) {
        if (is_string($cast) && enum_exists($cast) && is_subclass_of($cast, BackedEnum::class)
            && ! in_array($columna, $derivadas, true)) {
            $columnas[$columna] = $cast;
        }
    }

    return $columnas;
}

/**
 * El mismo listado como dataset posicional, que es lo que reparte Pest.
 *
 * @return array<int, array{0: string, 1: class-string<BackedEnum>}>
 */
function datasetColumnasConEnum(): array
{
    $filas = [];

    foreach (columnasConEnum() as $columna => $enum) {
        $filas[] = [$columna, $enum];
    }

    return $filas;
}

/**
 * Valores de las opciones marcadas como seleccionadas en un `<select>` por nombre.
 *
 * @return array<int, string>
 */
function opcionesSeleccionadas(string $html, string $name): array
{
    if (! preg_match('/<select\b[^>]*\bname="'.preg_quote($name, '/').'"[^>]*>(.*?)<\/select>/s', $html, $select)) {
        return [];
    }

    $marcadas = [];

    foreach (explode('<option', $select[1]) as $opcion) {
        if (! preg_match('/\bvalue="([^"]*)"/', $opcion, $valor)) {
            continue;
        }

        // `@selected` de Blade se compila a un atributo `selected`, con o sin valor.
        if (preg_match('/\bselected\b/', $opcion)) {
            $marcadas[] = $valor[1];
        }
    }

    return $marcadas;
}

/**
 * Revierte el escapado que `@js()` aplica al incrustar JSON en el HTML.
 *
 * Blade escapa las comillas como `\u0022`, así que un `toContain('"clave":"valor"')`
 * sobre el HTML crudo nunca encontraría nada.
 */
function jsDesescapado(string $html): string
{
    return str_replace(
        ['\u0022', '\u0027', '\u003C', '\u003E', '\u0026', '\/'],
        ['"', "'", '<', '>', '&', '/'],
        $html
    );
}

/**
 * Reabre la ficha y devuelve el HTML renderizado.
 */
function fichaRenderizada(object $test, $user): string
{
    return $test->actingAs($user)
        ->get('/infocentro/diagnostico')
        ->assertOk()
        ->getContent();
}

test('cada select de enum marca la opción ya guardada', function (string $columna, string $enum) {
    $valor = $enum::cases()[0]->value;
    $user = InfocentroUserFactory::new()->create();

    $user->infocentro->update([$columna => $valor]);

    expect(opcionesSeleccionadas(fichaRenderizada($this, $user), $columna))
        ->toBe([$valor], "El select «{$columna}» no marca la opción guardada.");
})->with(datasetColumnasConEnum());

test('sin valor guardado no se marca ninguna opción de la lista', function (string $columna, string $enum) {
    $user = InfocentroUserFactory::new()->create();

    // Sin `selected` el navegador muestra la primera opción, que es el
    // «Seleccione» en blanco. Lo que no debe pasar es que aparezca marcada una
    // respuesta concreta que el infocentro nunca eligió.
    expect(opcionesSeleccionadas(fichaRenderizada($this, $user), $columna))
        ->toBe([], "El select «{$columna}» marca una opción sin tener valor guardado.");
})->with(datasetColumnasConEnum());

test('todos los selects de enum del modelo existen en la ficha', function () {
    $html = fichaRenderizada($this, InfocentroUserFactory::new()->create());

    $ausentes = array_values(array_filter(
        array_keys(columnasConEnum()),
        fn (string $columna): bool => ! str_contains($html, 'name="'.$columna.'"')
    ));

    expect($ausentes)->toBe([], 'La ficha no tiene select para: '.implode(', ', $ausentes));
});

test('ningún select lleva el atributo value que el navegador ignora', function () {
    $html = fichaRenderizada($this, InfocentroUserFactory::new()->create());

    // El atributo `value` sobre un <select> no significa nada en HTML. Si
    // aparece, el valor real solo lo repoblaría Alpine tras arrancar, y el
    // marcado del servidor sería mentiroso.
    preg_match_all('/<select\b([^>]*)>/', $html, $selects);

    $conValue = array_values(array_filter(
        $selects[1],
        fn (string $atributos): bool => (bool) preg_match('/\bvalue="/', $atributos)
    ));

    expect($conValue)->toBe([], 'Hay '.count($conValue).' <select> con atributo value.');
});

test('tras un error de validación se muestra lo tecleado, no lo guardado', function () {
    $user = InfocentroUserFactory::new()->create();
    $user->infocentro->update(['interes_jovenes' => InteresDemandaEnum::No]);

    $this->actingAs($user)
        ->from('/infocentro/diagnostico')
        ->put('/infocentro/diagnostico', [
            'nombre' => '',
            'estado_id' => Estado::factory()->create()->id,
            'interes_jovenes' => InteresDemandaEnum::SiMuchos->value,
        ])
        ->assertSessionHasErrors();

    expect(opcionesSeleccionadas(fichaRenderizada($this, $user), 'interes_jovenes'))
        ->toBe([InteresDemandaEnum::SiMuchos->value]);
});

test('los selects anidados reciben su valor guardado por el payload', function () {
    $user = InfocentroUserFactory::new()->create();

    VinculacionMincytFactory::new()->for($user->infocentro)->create([
        'estado' => EstadoProyectoMincytEnum::Concluido,
    ]);

    FacilitadorFactory::new()->for($user->infocentro)->create([
        'nivel_academico' => NivelAcademicoEnum::cases()[0],
    ]);

    // Estos selects no llevan `@selected`: Alpine los repuebla con x-model desde
    // `window.datosFicha`. Si el payload no trae el valor, el operador vería el
    // desplegable en blanco.
    $payload = jsDesescapado(fichaRenderizada($this, $user));

    expect(str_contains($payload, '"estado":"'.EstadoProyectoMincytEnum::Concluido->value.'"'))
        ->toBeTrue('El payload no trae el estado de la institución vinculada.');

    expect(str_contains($payload, '"nivel_academico":"'.NivelAcademicoEnum::cases()[0]->value.'"'))
        ->toBeTrue('El payload no trae el nivel académico del facilitador.');
});
