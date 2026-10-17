<?php

use App\Enums\DimensionPerfilamientoEnum;
use App\Enums\PrioridadIntervencionEnum;
use App\Enums\TipoInfocentroEnum;
use App\Models\Estado;
use Database\Factories\InfocentroUserFactory;
use Illuminate\Testing\TestResponse;

/**
 * Guardar la ficha con el bloque de perfilamiento.
 *
 * @param  array<string, mixed>  $extra
 */
function guardarPerfil($user, array $puntuaciones = [], array $extra = []): TestResponse
{
    return test()->actingAs($user)->post('/infocentro/diagnostico', [
        '_method' => 'PUT',
        'nombre' => 'Infocentro',
        'estado_id' => Estado::factory()->create()->id,
        'activo' => '1',
        'puntuaciones' => $puntuaciones,
        ...$extra,
    ]);
}

/**
 * Un juego completo de puntuaciones, todas al máximo salvo donde se indique.
 *
 * @param  array<string, int>  $cambios
 * @return array<string, string>
 */
function puntuacionesDeEjemplo(array $cambios = []): array
{
    $puntuaciones = [];

    foreach (DimensionPerfilamientoEnum::cases() as $dimension) {
        $puntuaciones[$dimension->value] = (string) ($cambios[$dimension->value] ?? 3);
    }

    return $puntuaciones;
}

/**
 * Reparte un total entre las siete dimensiones, de tres en tres.
 *
 * @return array<string, string>
 */
function puntuacionesConTotal(int $total): array
{
    $puntuaciones = [];
    $restante = $total;

    foreach (DimensionPerfilamientoEnum::cases() as $dimension) {
        $puntos = min(DimensionPerfilamientoEnum::PUNTUACION_MAXIMA, $restante);
        $puntuaciones[$dimension->value] = (string) $puntos;
        $restante -= $puntos;
    }

    return $puntuaciones;
}

test('la ficha muestra el bloque de perfilamiento con las siete dimensiones', function () {
    $html = $this->actingAs(InfocentroUserFactory::new()->create())
        ->get('/infocentro/diagnostico')
        ->assertOk()
        ->getContent();

    expect(str_contains($html, htmlspecialchars(__('Perfilamiento y clasificación del infocentro'), ENT_QUOTES)))
        ->toBeTrue('Falta el encabezado del bloque de perfilamiento.');

    foreach (DimensionPerfilamientoEnum::cases() as $dimension) {
        expect(str_contains($html, 'name="puntuaciones['.$dimension->value.']"'))->toBeTrue(
            "Falta el desplegable de «{$dimension->label()}».",
        );
    }
});

test('la sección de perfilamiento llega entera al navegador', function () {
    $html = $this->actingAs(InfocentroUserFactory::new()->create())
        ->get('/infocentro/diagnostico')
        ->assertOk()
        ->getContent();

    // Un atributo `:class` con el ternario partido en varias líneas hace que
    // Blade cierre la etiqueta en la primera comilla doble: la sección se
    // renderiza hasta el encabezado y todo lo demás se pierde en silencio.
    // El HTML servido engaña, así que se comprueba que el contenido esté
    // DENTRO del section y no en cualquier parte de la página.
    $inicio = strpos($html, '<section aria-labelledby="perfilamiento"');
    $fin = $inicio === false ? false : strpos($html, '</section>', $inicio);
    $seccion = $inicio === false ? '' : substr($html, $inicio, $fin - $inicio);

    expect($seccion)->not->toBe('')
        ->and(str_contains($seccion, 'J.1. Puntuación por dimensiones'))->toBeTrue(
            'La sección se corta antes de J.1: revisa los atributos Alpine con comillas anidadas o salto de línea.',
        )
        ->and(str_contains($seccion, 'J.2. Clasificación del infocentro'))->toBeTrue(
            'La sección se corta antes de J.2.',
        )
        ->and(str_contains($seccion, 'J.3. Prioridad de intervención'))->toBeTrue(
            'La sección se corta antes de J.3.',
        )
        ->and(str_contains($seccion, 'J.4. Recomendaciones específicas'))->toBeTrue(
            'La sección se corta antes de J.4.',
        );

    // Los tres tipos se pintan como tarjetas con su texto de intervención.
    foreach (TipoInfocentroEnum::cases() as $tipo) {
        expect($seccion)->toContain($tipo->label())
            ->and($seccion)->toContain($tipo->intervencion());
    }
});

test('los tipos llegan a la vista indexados por su valor de enum', function () {
    $html = $this->actingAs(InfocentroUserFactory::new()->create())
        ->get('/infocentro/diagnostico')
        ->assertOk()
        ->getContent();

    // Alpine busca el primer umbral que alcance el total sobre las claves de
    // este objeto. Si `@js` lo delivers como lista, la búsqueda nunca
    // encuentra 'a', 'b' ni 'c' y el resaltado se queda siempre en el
    // primer tipo por defecto sin que se note en la vista.
    // `@js()` envuelve los objetos anidados en `JSON.parse('…')` y escapa las
    // comillas como \u0022, así que se desescapa antes de mirar el contenido.
    preg_match('/tipos: JSON\.parse\(\'(.*?)\'\)/s', $html, $c);
    $json = html_entity_decode(str_replace(['\\u0022', '\\\\'], ['"', '\\'], $c[1] ?? ''), ENT_QUOTES);

    expect($json)->not->toBe('')
        ->and(str_starts_with(ltrim($json), '{"a":'))->toBeTrue(
            'La configuración de tipos debe ser un objeto indexado por a/b/c, no una lista.',
        );

    foreach (TipoInfocentroEnum::cases() as $tipo) {
        expect($json)->toContain('"'.$tipo->value.'":{"etiqueta"')
            ->and($json)->toContain('"desde":'.$tipo->desde());
    }
});

test('el resaltado de la clasificación se decide con los datos de la vista', function () {
    $html = $this->actingAs(InfocentroUserFactory::new()->create())
        ->get('/infocentro/diagnostico')
        ->assertOk()
        ->getContent();

    // Cada tarjeta compara contra `tipoActual`, así que los tres valores del
    // enum tienen que aparecer en la expresión de Alpine.
    foreach (TipoInfocentroEnum::cases() as $tipo) {
        expect(str_contains($html, "tipoActual === '{$tipo->value}'"))->toBeTrue(
            "Falta la comparación con el Tipo {$tipo->value}.",
        );
    }
});

test('cada opción lleva la leyenda de SU propio número', function () {
    // La leyenda del PDF es lo que define cada punto, así que tiene que estar
    // pegada a su valor. unchecked: un enunciado que solo busca el texto
    // anywhere passaría aunque cada número estuviera descolgado de su
    // descripción.
    foreach (DimensionPerfilamientoEnum::cases() as $dimension) {
        $opciones = $dimension->opciones();
        $escala = $dimension->escala();

        expect($opciones)->toHaveCount(4);

        foreach ($escala as $puntos => $descripcion) {
            $opciones[$puntos] === $descripcion
                ? expect($opciones[$puntos])->toBe($descripcion, "El 0 de «{$dimension->label()}» no lleva su descripción.")
                : expect($opciones[$puntos])->toBe("{$puntos} puntos — {$descripcion}", "El {$puntos} de «{$dimension->label()}» no lleva su descripción.");
        }
    }
});

test('la leyenda aparece pegada al valor en el desplegable', function () {
    $html = $this->actingAs(InfocentroUserFactory::new()->create())
        ->get('/infocentro/diagnostico')
        ->assertOk()
        ->getContent();

    // Se comprueba valor y texto en la misma etiqueta <option>, no por separado.
    expect(str_contains($html, '<option value="0" >3 puntos — Fibra óptica'))->toBeFalse(
        'El 0 no puede quedar etiquetado como si fuera el 3.',
    )
        ->and(str_contains($html, '<option value="3" >3 puntos — Fibra óptica, estable, gratuito'))->toBeTrue()
        ->and(str_contains($html, '<option value="0" >Sin internet'))->toBeTrue();
});

test('un total de 21 se guarda como Tipo A con el total correcto', function () {
    $user = InfocentroUserFactory::new()->create();

    guardarPerfil($user, puntuacionesDeEjemplo())->assertSessionHasNoErrors();

    $infocentro = $user->infocentro->refresh();

    expect($infocentro->puntuacion_total)->toBe(21)
        ->and($infocentro->tipo_infocentro)->toBe(TipoInfocentroEnum::A)
        ->and($infocentro->puntuaciones)->toHaveCount(7)
        ->and($infocentro->puntuaciones['conectividad'])->toBe(3);
});

test('las puntuaciones se guardan como enteros', function () {
    $user = InfocentroUserFactory::new()->create();

    guardarPerfil($user, puntuacionesDeEjemplo([
        'conectividad' => 2,
        'operatividad' => 1,
    ]))->assertSessionHasNoErrors();

    $puntuaciones = $user->infocentro->refresh()->puntuaciones;

    expect($puntuaciones['conectividad'])->toBe(2)
        ->and($puntuaciones['operatividad'])->toBe(1)
        ->and($puntuaciones)->toHaveCount(7);
});

test('una dimensión de puntuación desconocida se rechaza', function () {
    $user = InfocentroUserFactory::new()->create();

    guardarPerfil($user, [], [
        'puntuaciones' => array_merge(puntuacionesDeEjemplo(), ['inventada' => '3']),
    ])->assertSessionHasErrors('puntuaciones.inventada');

    expect($user->infocentro->refresh()->puntuaciones)->toBeNull();
});

test('el total y el tipo se calculan, no se aceptan del formulario', function () {
    $user = InfocentroUserFactory::new()->create();

    // Quien manipule el payload no puede declararse Tipo A con 3 puntos: las
    // puntuaciones son 7 (una por dimensión) y el tipo sale de su suma.
    guardarPerfil($user, puntuacionesDeEjemplo([
        'conectividad' => 0,
        'operatividad' => 0,
        'facilitadores' => 0,
        'equipamiento' => 0,
        'articulacion_comunitaria' => 0,
        'oferta_formativa' => 0,
        'vinculacion_mincyt' => 3,
    ]), [
        'puntuacion_total' => 21,
        'tipo_infocentro' => 'a',
    ])->assertSessionHasNoErrors();

    $infocentro = $user->infocentro->refresh();

    expect($infocentro->puntuacion_total)->toBe(3)
        ->and($infocentro->tipo_infocentro)->toBe(TipoInfocentroEnum::C);
});

test('un bloque entero vacío se guarda como sin perfilar', function () {
    $user = InfocentroUserFactory::new()->create();

    // La ficha guarda sin perfilar mientras el diagnosticador no toque J.1.
    guardarPerfil($user, [])->assertSessionHasNoErrors();

    $infocentro = $user->infocentro->refresh();

    expect($infocentro->puntuaciones)->toBe([])
        ->and($infocentro->puntuacion_total)->toBeNull()
        ->and($infocentro->tipo_infocentro)->toBeNull();
});

test('las siete dimensiones son obligatorias en bloque', function () {
    $user = InfocentroUserFactory::new()->create();

    // Empezar a perfilar y dejarlo a medias daría una clasificación sin
    // sentido, así que en cuanto se marca una, las otras seis se exigen.
    guardarPerfil($user, ['conectividad' => '2'])->assertSessionHasErrors([
        'puntuaciones.operatividad',
        'puntuaciones.facilitadores',
        'puntuaciones.equipamiento',
        'puntuaciones.articulacion_comunitaria',
        'puntuaciones.oferta_formativa',
        'puntuaciones.vinculacion_mincyt',
    ]);

    expect($user->infocentro->refresh()->puntuacion_total)->toBeNull()
        ->and($user->infocentro->tipo_infocentro)->toBeNull();
});

test('se rechaza una puntuación fuera de 0 a 3', function (string $valor) {
    $user = InfocentroUserFactory::new()->create();

    guardarPerfil($user, ['conectividad' => $valor])
        ->assertSessionHasErrors('puntuaciones.conectividad');
})->with(['4', '-1', 'mucho']);

test('cada total cae en el tipo que le corresponde en el PDF', function (int $total, TipoInfocentroEnum $esperado) {
    $user = InfocentroUserFactory::new()->create();

    guardarPerfil($user, puntuacionesConTotal($total))->assertSessionHasNoErrors();

    $infocentro = $user->infocentro->refresh();

    expect($infocentro->puntuacion_total)->toBe($total)
        ->and($infocentro->tipo_infocentro)->toBe($esperado);
})
    ->with([
        [0, TipoInfocentroEnum::C],    // 0-7  Por recuperar
        [7, TipoInfocentroEnum::C],    // borde superior de C
        [8, TipoInfocentroEnum::B],    // 8-14 En consolidación
        [14, TipoInfocentroEnum::B],   // borde superior de B
        [15, TipoInfocentroEnum::A],   // 15-21 Núcleo piloto
        [21, TipoInfocentroEnum::A],   // máximo
    ]);

test('los tres rangos cubren los 21 totales sin huecos ni solapes', function () {
    // El PDF reparte 0-7, 8-14 y 15-21. Si alguien mueve un umbral, un total
    // puede quedarse sin clasificar o caer en dos a la vez.
    $cubiertos = [];

    foreach (TipoInfocentroEnum::cases() as $tipo) {
        $limite = $tipo === TipoInfocentroEnum::A
            ? DimensionPerfilamientoEnum::TOTAL_MAXIMO
            : TipoInfocentroEnum::cases()[array_search($tipo, TipoInfocentroEnum::cases(), true) - 1]->desde() - 1;

        for ($puntos = $tipo->desde(); $puntos <= $limite; $puntos++) {
            $cubiertos[$puntos][] = $tipo->value;
        }
    }

    for ($puntos = 0; $puntos <= DimensionPerfilamientoEnum::TOTAL_MAXIMO; $puntos++) {
        expect(TipoInfocentroEnum::desdePuntuacion($puntos))->not->toBeNull();

        if (isset($cubiertos[$puntos])) {
            expect($cubiertos[$puntos])->toHaveCount(1, "El total $puntos cae en más de un tipo.");
        }
    }

    // Y que la cadena de umbrales sea realmente contigua.
    $umbrales = array_map(fn (TipoInfocentroEnum $t): int => $t->desde(), TipoInfocentroEnum::cases());
    sort($umbrales);

    expect($umbrales)->toBe([0, 8, 15])
        ->and(max($umbrales))->toBeLessThan(DimensionPerfilamientoEnum::TOTAL_MAXIMO);
});

test('una ficha guardada conserva su total aunque se manden otras puntuaciones', function () {
    $user = InfocentroUserFactory::new()->create();

    guardarPerfil($user, puntuacionesDeEjemplo())->assertSessionHasNoErrors();
    expect($user->infocentro->refresh()->tipo_infocentro)->toBe(TipoInfocentroEnum::A);

    // El total y la clasificación se deducen en cada guardado, pero como la
    // ficha se cierra al guardarla, ya no hay un segundo guardado donde
    // recalcularlos: se quedan los del primero.
    guardarPerfil($user, puntuacionesDeEjemplo([
        'conectividad' => 0,
        'operatividad' => 0,
        'facilitadores' => 0,
        'equipamiento' => 0,
        'articulacion_comunitaria' => 0,
        'oferta_formativa' => 0,
        'vinculacion_mincyt' => 0,
    ]))->assertSessionHas('aviso');

    $infocentro = $user->infocentro->refresh();

    expect($infocentro->puntuacion_total)->toBe(21)
        ->and($infocentro->tipo_infocentro)->toBe(TipoInfocentroEnum::A);
});

test('las puntuaciones y la clasificación vuelven a pintarse al recargar', function () {
    $user = InfocentroUserFactory::new()->create();

    guardarPerfil($user, puntuacionesDeEjemplo([
        'conectividad' => 2,
        'operatividad' => 0,
        'facilitadores' => 0,
        'equipamiento' => 0,
        'articulacion_comunitaria' => 0,
        'oferta_formativa' => 0,
        'vinculacion_mincyt' => 0,
    ]))->assertSessionHasNoErrors();

    $html = $this->actingAs($user)->get('/infocentro/diagnostico')
        ->assertOk()
        ->getContent();

    // La opción 2 de conectividad es la que quedó marcada.
    expect(substr_count($html, 'value="2" selected'))->toBeGreaterThan(0)
        ->and(str_contains($html, 'name="puntuaciones[conectividad]"'))->toBeTrue();
});

test('una puntuación rechazada no borra lo que ya estaba guardado', function () {
    $user = InfocentroUserFactory::new()->create();

    // La ficha arranca con una conectividad ya guardada. Es un estado que no
    // sale de un guardado por el formulario —guardar cierra la ficha— sino de
    // los datos que trae el infocentro; sirve para distinguir «se pinta lo que
    // se acaba de enviar» de «se pinta lo que hay en el registro».
    $user->infocentro->update(['puntuaciones' => ['conectividad' => 5]]);

    // Guardado donde la conectividad baja a 1 pero falta otra dimensión: la
    // vista debe enseñar lo que se acaba de enviar, no lo que había quedado
    // guardado antes del rechazo.
    $puntuaciones = puntuacionesDeEjemplo(['conectividad' => 1]);
    unset($puntuaciones[DimensionPerfilamientoEnum::Facilitadores->value]);

    guardarPerfil($user, $puntuaciones)
        ->assertSessionHasErrors('puntuaciones.facilitadores');

    $html = $this->actingAs($user)->get('/infocentro/diagnostico')->assertOk()->getContent();

    expect(str_contains($html, 'value="1" selected'))->toBeTrue(
        'Tras un error de validación debe verse lo enviado, no lo guardado antes.',
    );
});

test('la prioridad de intervención y las recomendaciones se guardan', function () {
    $user = InfocentroUserFactory::new()->create();

    guardarPerfil($user, puntuacionesDeEjemplo(), [
        'prioridad_intervencion' => PrioridadIntervencionEnum::CortoPlazo->value,
        'recomendaciones' => 'Reponer el cableado y abrir dos tardes de formación.',
    ])->assertSessionHasNoErrors();

    $infocentro = $user->infocentro->refresh();

    expect($infocentro->prioridad_intervencion)->toBe(PrioridadIntervencionEnum::CortoPlazo)
        ->and($infocentro->recomendaciones)->toBe('Reponer el cableado y abrir dos tardes de formación.');
});

test('se rechaza una prioridad de intervención inventada', function () {
    $user = InfocentroUserFactory::new()->create();

    guardarPerfil($user, puntuacionesDeEjemplo(), ['prioridad_intervencion' => 'urgente'])
        ->assertSessionHasErrors('prioridad_intervencion');
});

test('la prioridad y las recomendaciones son opcionales', function () {
    $user = InfocentroUserFactory::new()->create();

    guardarPerfil($user, puntuacionesDeEjemplo())->assertSessionHasNoErrors();

    $infocentro = $user->infocentro->refresh();

    expect($infocentro->prioridad_intervencion)->toBeNull()
        ->and($infocentro->recomendaciones)->toBeNull()
        ->and($infocentro->puntuacion_total)->toBe(21);
});

test('el total máximo del enum cuadra con las dimensiones por las que se compone', function () {
    $suma = array_sum(
        array_map(fn (DimensionPerfilamientoEnum $d): int => DimensionPerfilamientoEnum::PUNTUACION_MAXIMA, DimensionPerfilamientoEnum::cases())
    );

    expect(DimensionPerfilamientoEnum::TOTAL_MAXIMO)->toBe($suma)
        ->and($suma)->toBe(21);
});

test('cada dimensión describe los cuatro puntos de su escala', function () {
    foreach (DimensionPerfilamientoEnum::cases() as $dimension) {
        $escala = $dimension->escala();

        expect(array_keys($escala))->toBe([3, 2, 1, 0], "La escala de «{$dimension->label()}» no va de 3 a 0.")
            ->and($dimension->opciones())->toHaveCount(4);
    }
});
