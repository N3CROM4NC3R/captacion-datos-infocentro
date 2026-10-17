<?php

use App\Models\Estado;
use App\Models\Municipio;
use App\Models\Parroquia;
use Database\Factories\InfocentroUserFactory;

/**
 * Un control con `x-model` no puede llevar `disabled` como atributo estático.
 *
 * `disabled` escrito en el HTML es una decisión del servidor y Alpine no puede
 * deshacerla. En la sección A pasaba con los desplegables de municipio y parroquia:
 * seInactive el estado, el municipio quedaba bloqueado para siempre. Es la tercera
 * vez que el mismo error aparecía en el proyecto — las otras dos fueron el
 * `x-show="false"` de la sección F y los `x-model="actividades[...]"` sin comillas.
 *
 * El guard es general: cubre cualquier sección futura sin nombrarlas.
 */
test('ningún control enlazado a Alpine se bloquea con un disabled estático', function () {
    $html = $this->actingAs(InfocentroUserFactory::new()->create())
        ->get('/infocentro/diagnostico')
        ->assertOk()
        ->getContent();

    preg_match_all('/<(select|input|textarea)\b[^>]*>/', $html, $etiquetas);

    $bloqueados = [];

    foreach ($etiquetas[0] as $etiqueta) {
        $tieneModelo = (bool) preg_match('/\bx-model="/', $etiqueta);
        $tieneNombre = (bool) preg_match('/\bname="[^"]+"/', $etiqueta);

        // `x-bind:disabled` sí es reactivo: lo gestiona Alpine.
        $disabledEstatico = (bool) preg_match('/(?<!x-bind:)\sdisabled(=|[\s>])/', $etiqueta);

        // Las casillas «No» son espejos decorativos: van `disabled` a propósito y
        // sin `name`, de modo que nunca llegan al servidor.
        if ($tieneModelo && $tieneNombre && $disabledEstatico) {
            $bloqueados[] = preg_replace('/\s+/', ' ', $etiqueta);
        }
    }

    expect($bloqueados)->toBe([], 'Controles que Alpine no puede habilitar: '.implode(' | ', $bloqueados));
});

test('los espejos «No» siguen deshabilitados y sin nombre', function () {
    $html = $this->actingAs(InfocentroUserFactory::new()->create())
        ->get('/infocentro/diagnostico')
        ->assertOk()
        ->getContent();

    preg_match_all('/<input[^>]*\bdisabled\b[^>]*>/', $html, $deshabilitados);

    $conNombre = array_values(array_filter(
        $deshabilitados[0],
        fn (string $etiqueta): bool => (bool) preg_match('/\bname="/', $etiqueta)
    ));

    expect($conNombre)->toBe([], 'Espejos deshabilitados con name: '.implode(' | ', $conNombre));
});

test('el desplegable de municipio se habilita al elegir estado', function () {
    $user = InfocentroUserFactory::new()->create();

    // Sin estado guardado, el control queda gobernado por Alpine y no por un
    // atributo estático: nace sin `disabled` en el marcado.
    $html = $this->actingAs($user)->get('/infocentro/diagnostico')
        ->assertOk()
        ->getContent();

    expect(str_contains($html, 'x-bind:disabled="!estadoId"'))->toBeTrue()
        ->and(str_contains($html, 'x-bind:disabled="!municipioId"'))->toBeTrue()
        ->and(str_contains($html, 'blank($estadoId)'))->toBeFalse()
        ->and(str_contains($html, 'blank($municipioId)'))->toBeFalse();
});

test('los tres desplegables se guardan y se repueblan en cascada', function () {
    $user = InfocentroUserFactory::new()->create();

    // RefreshDatabase migra pero no siembra, así que el catálogo geográfico se
    // construye con factories, como en el resto de la suite.
    $estado = Estado::factory()->create();
    $municipio = Municipio::factory()->create(['estado_id' => $estado->id]);
    $parroquia = Parroquia::factory()->create(['municipio_id' => $municipio->id]);

    $this->actingAs($user)->put('/infocentro/diagnostico', [
        'nombre' => 'Infocentro',
        'estado_id' => $estado->id,
        'municipio_id' => $municipio->id,
        'parroquia_id' => $parroquia->id,
        'activo' => '1',
    ])->assertSessionHasNoErrors();

    $html = $this->actingAs($user)->get('/infocentro/diagnostico')
        ->assertOk()
        ->getContent();

    $guardado = $user->infocentro->refresh();

    expect($guardado->estado_id)->toBe($estado->id)
        ->and($guardado->municipio_id)->toBe($municipio->id)
        ->and($guardado->parroquia_id)->toBe($parroquia->id);

    // El estado se pinta en el servidor, así que su opción va marcada.
    expect((bool) preg_match(
        '/<option value="'.preg_quote((string) $estado->id, '/').'"\s+selected/',
        $html
    ))->toBeTrue('El desplegable de estado no marca el valor guardado.');

    // Municipio y parroquia pintan sus opciones con <template x-for>, así que no
    // pueden marcarse en el HTML: lo que debe llegar es el valor en el payload
    // que Alpine usa para seleccionarlas. `@js()` sobre un entero emite un
    // número desnudo, no JSON.
    // Municipio y parroquia pintan sus opciones con <template x-for>, así que no
    // pueden marcarse en el HTML: lo que debe llegar es el valor en el payload
    // que Alpine usa para seleccionarlas.
    //
    // Con la ficha ya guardada la página es la de solo lectura, que publica el
    // payload como JSON. Se decodifica en vez de buscar texto suelto, porque
    // `@js()` escapa las comillas y el formato del formulario editable no es
    // el de aquí.
    expect($html)->toContain('seleccion')
        ->and(payloadDeFicha($html)['seleccion'])
        ->toMatchArray([
            'estado' => $estado->id,
            'municipio' => $municipio->id,
            'parroquia' => $parroquia->id,
        ]);
});

/**
 * El payload `window.datosFicha` de la página, ya decodificado.
 *
 * @return array<string, mixed>
 */
function payloadDeFicha(string $html): array
{
    preg_match("/window\\.datosFicha = JSON\\.parse\\('(.*?)'\\)/s", $html, $encontrado);

    return json_decode(str_replace(['\\u0022', '\\\\'], ['"', '\\'], $encontrado[1] ?? '{}'), true) ?: [];
}
