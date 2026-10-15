<?php

use App\Enums\TipoActividadEnum;
use App\Models\Estado;
use Database\Factories\InfocentroUserFactory;

/**
 * Preguntas de cierre en formato «( ) Sí ( ) No», como las del formulario.
 *
 * La casilla de «No» es un espejo de la real: va `disabled` y **sin `name`**,
 * que es lo que garantiza que jamás llegue al servidor. Si alguna vez se le
 * añadiera un `name`, podría contradecir al dato guardado sin que nadie se entere.
 */

/**
 * @return array<int, string>
 */
function camposDeCierre(): array
{
    $campos = [
        'tiene_facilitador_titular',
        'vinculacion_autoridad',
        'vinculacion_mincyt',
        'ciencia_ciudadana',
    ];

    foreach (TipoActividadEnum::cases() as $tipo) {
        $campos[] = "actividades[activa][{$tipo->value}]";
    }

    return $campos;
}

test('cada pregunta de cierre ofrece las casillas Sí y No', function () {
    $html = $this->actingAs(InfocentroUserFactory::new()->create())
        ->get('/infocentro/diagnostico')
        ->assertOk()
        ->getContent();

    $sinPareja = [];

    foreach (camposDeCierre() as $campo) {
        if (! str_contains($html, "name=\"{$campo}\"")) {
            $sinPareja[] = $campo;
        }
    }

    expect($sinPareja)->toBe([], 'Preguntas sin casilla de Sí: '.implode(', ', $sinPareja));
    // 4 preguntas sueltas + 9 actividades = 13 pares, más el «No» informativo
    // que lleva el bloque H.2.
    expect(substr_count($html, '>No</span>'))->toBe(count(camposDeCierre()) + 1);
});

test('ninguna casilla de No se envía al servidor', function () {
    $html = $this->actingAs(InfocentroUserFactory::new()->create())
        ->get('/infocentro/diagnostico')
        ->assertOk()
        ->getContent();

    // Cada «No» es un input deshabilitado y sin atributo name: el navegador no
    // lo incluye en el envío, así que no puede contradecir al campo real.
    preg_match_all('/<input[^>]*\bdisabled\b[^>]*>/', $html, $deshabilitados);

    $conName = array_values(array_filter(
        $deshabilitados[0],
        fn (string $etiqueta): bool => (bool) preg_match('/\bname="[^"]*(?:no|negativo)[^"]*"/i', $etiqueta)
    ));

    expect($conName)->toBe([], 'Casillas de No con name: '.implode(' | ', $conName));
});

test('la casilla de No espeja el estado real mediante Alpine', function () {
    $html = $this->actingAs(InfocentroUserFactory::new()->create())
        ->get('/infocentro/diagnostico')
        ->assertOk()
        ->getContent();

    // La de «Sí» manda en `model` y la de «No` en el inverso, de modo que no
    // pueden quedar ambas marcadas. `toContain()` de Pest es variádico y no
    // admite un segundo argumento de mensaje, así que se comprueba aparte.
    preg_match_all('/<input[^>]*\bx-model="([^"]+)"[^>]*>/', $html, $modelos);

    $ausentes = [];

    // Los nombres son los del estado Alpine, que no coinciden siempre con la
    // clave del payload: H.1 viaja como `vinculacionMincyt` pero se lee de
    // `vinculado`.
    foreach (['tieneTitular', 'vinculacionAutoridad', 'vinculado', 'cienciaCiudadana', 'sinInstituciones'] as $estado) {
        $invertido = in_array('!'.$estado, $modelos[1], true);

        if (! in_array($estado, $modelos[1], true) && ! $invertido) {
            $ausentes[] = $estado;
        }
    }

    expect($ausentes)->toBe([], 'Pares sin estado y espejo: '.implode(', ', $ausentes));
});

test('las expresiones Alpine de las actividades llevan comillas en la clave', function () {
    $html = $this->actingAs(InfocentroUserFactory::new()->create())
        ->get('/infocentro/diagnostico')
        ->assertOk()
        ->getContent();

    // Sin las comillas, `actividades[robotica]` es una variable inexistente y no
    // una clave del objeto: Alpine lo resuelve a false y el par Sí/No deja de
    // espejarse sin avisar. Solo se detecta con Alpine en marcha.
    // Blade escapa las comillas del atributo como `&#039;`; el navegador las
    // decodifica antes de que Alpine lea la expresión, así que la comparación
    // se hace sobre el texto ya decodificado.
    $decodificada = html_entity_decode($html);

    $malas = [];

    foreach (TipoActividadEnum::cases() as $tipo) {
        foreach (["actividades['{$tipo->value}']", "!actividades['{$tipo->value}']"] as $esperada) {
            if (! str_contains($decodificada, 'x-model="'.$esperada.'"')) {
                $malas[] = $esperada;
            }
        }
    }

    expect($malas)->toBe([], 'Expresiones Alpine mal formadas: '.implode(', ', $malas));
});

test('D.1 publica su estado en el payload', function () {
    $html = $this->actingAs(InfocentroUserFactory::new()->create())
        ->get('/infocentro/diagnostico')
        ->assertOk()
        ->getContent();

    expect($html)->toContain('tieneFacilitadorTitular:');
});

test('un No marcado no altera lo que se guarda', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', [
        'nombre' => 'Infocentro',
        'estado_id' => Estado::factory()->create()->id,
        'activo' => '1',
        // Las casillas de No no viajan: el campo real sigue sin tocarse.
        'tiene_facilitador_no' => '1',
        'ciencia_ciudadana' => '1',
        'ciencia_ciudadana_detalle' => 'Mapeo de quebradas',
    ])->assertSessionHasNoErrors();

    expect($user->infocentro->refresh()->tiene_facilitador_titular)->toBeFalse()
        ->and($user->infocentro->ciencia_ciudadana)->toBeTrue();
});
