<?php

use App\Enums\FrecuenciaEnum;
use App\Enums\TipoActividadEnum;
use App\Enums\TipoEnergiaEnum;
use App\Enums\TipoEquipoEnum;
use App\Models\Estado;
use Database\Factories\InfocentroUserFactory;

/**
 * Nombres de todos los campos que las secciones F y G deben renderizar.
 *
 * Se derivan de los enums para que no quede obsoleta: si se agrega un tipo de
 * equipo, energía o actividad, el formulario tiene que exponer sus campos y el
 * test lo exige sin tocar este archivo.
 *
 * @return array<int, string>
 */
function camposDeEquipos(): array
{
    $campos = [];

    foreach (TipoEquipoEnum::cases() as $tipo) {
        $base = 'equipos';

        $campos[] = "{$base}[cantidad][{$tipo->value}]";
        $campos[] = "{$base}[estado][{$tipo->value}]";
        $campos[] = "{$base}[detalle][{$tipo->value}]";
        $campos[] = "{$base}[observaciones][{$tipo->value}]";

        if ($tipo->tieneDesglose()) {
            $campos[] = "{$base}[funcionando][{$tipo->value}]";
            $campos[] = "{$base}[fuera_servicio][{$tipo->value}]";
        }
    }

    return $campos;
}

/**
 * @return array<int, string>
 */
function camposDeEnergias(): array
{
    $campos = [];

    foreach (TipoEnergiaEnum::cases() as $tipo) {
        $base = 'energias';

        $campos[] = "{$base}[estado][{$tipo->value}]";
        $campos[] = "{$base}[detalle][{$tipo->value}]";
        $campos[] = "{$base}[observaciones][{$tipo->value}]";

        if ($tipo->cantidadFija()) {
            $campos[] = "{$base}[cantidad][{$tipo->value}]";
        }
    }

    return $campos;
}

/**
 * @return array<int, string>
 */
function camposDeActividades(): array
{
    $campos = [];

    foreach (TipoActividadEnum::cases() as $tipo) {
        $campos[] = "actividades[activa][{$tipo->value}]";
        $campos[] = "actividades[frecuencia][{$tipo->value}]";
        $campos[] = "actividades[participantes][{$tipo->value}]";
        $campos[] = "actividades[detalle][{$tipo->value}]";
    }

    return $campos;
}

/**
 * ¿Existe un control real (input o select) con ese nombre?
 */
function controlExiste(string $html, string $name): bool
{
    return (bool) preg_match(
        '/<(input|select|textarea)\b[^>]*\bname="'.preg_quote($name, '/').'"[^>]*>/',
        $html
    );
}

/**
 * Reabre la ficha y devuelve el HTML renderizado.
 */
function htmlDeLaFicha(object $test, $user): string
{
    return $test->actingAs($user)
        ->get('/infocentro/diagnostico')
        ->assertOk()
        ->getContent();
}

test('ninguna etiqueta de componente Blade llega sin compilar al HTML', function () {
    $html = htmlDeLaFicha($this, InfocentroUserFactory::new()->create());

    // Si un atributo de un <x-algo> lleva comillas dobles anidadas, el parser de
    // Blade no reconoce el tag y lo emite literal. En el navegador queda un
    // elemento desconocido en lugar del campo, y ese dato nunca se envía.
    preg_match_all('/<x-[a-z][\w-]*/i', $html, $sobrantes);

    expect($sobrantes[0])
        ->toBe([], 'Hay etiquetas Blade sin compilar en el HTML: '.implode(', ', array_unique($sobrantes[0])));
});

test('ninguna etiqueta queda visible en el navegador', function () {
    $html = htmlDeLaFicha($this, InfocentroUserFactory::new()->create());

    expect($html)->not->toContain('<x-text-input')
        ->not->toContain('<x-select-input')
        ->not->toContain('<x-input-label')
        ->not->toContain('<x-input-error');
});

test('se renderizan todos los campos de equipos', function () {
    $html = htmlDeLaFicha($this, InfocentroUserFactory::new()->create());

    $ausentes = array_values(array_filter(
        camposDeEquipos(),
        fn (string $campo): bool => ! controlExiste($html, $campo)
    ));

    expect($ausentes)->toBe([], 'Campos de equipos ausentes: '.implode(', ', $ausentes));
});

test('se renderizan todos los campos de energías', function () {
    $html = htmlDeLaFicha($this, InfocentroUserFactory::new()->create());

    $ausentes = array_values(array_filter(
        camposDeEnergias(),
        fn (string $campo): bool => ! controlExiste($html, $campo)
    ));

    expect($ausentes)->toBe([], 'Campos de energías ausentes: '.implode(', ', $ausentes));
});

test('se renderizan todos los campos de actividades', function () {
    $html = htmlDeLaFicha($this, InfocentroUserFactory::new()->create());

    $ausentes = array_values(array_filter(
        camposDeActividades(),
        fn (string $campo): bool => ! controlExiste($html, $campo)
    ));

    expect($ausentes)->toBe([], 'Campos de actividades ausentes: '.implode(', ', $ausentes));
});

test('los campos numéricos y de texto de F y G aceptan el valor guardado', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', [
        'nombre' => 'Infocentro',
        'estado_id' => Estado::factory()->create()->id,
        'equipos' => [
            'cantidad' => ['computadoras' => 12],
            'funcionando' => ['computadoras' => 9],
            'fuera_servicio' => ['computadoras' => 3],
            'detalle' => ['computadoras' => 'Linux Mint'],
            'observaciones' => ['computadoras' => 'Faltan monitores'],
        ],
        'energias' => [
            'cantidad' => ['plantas_electricas' => 2],
            'detalle' => ['plantas_electricas' => 'Diésel 5kVA'],
            'observaciones' => ['plantas_electricas' => 'Combustible inestable'],
        ],
        'actividades' => [
            // El controller solo guarda los tipos marcados como activos, y la
            // frecuencia es obligatoria en ese caso.
            'activa' => ['robotica' => 1],
            'frecuencia' => ['robotica' => FrecuenciaEnum::Semanal->value],
            'participantes' => ['robotica' => 18],
            'detalle' => ['robotica' => 'Taller de sumo'],
        ],
    ])->assertSessionHasNoErrors();

    $html = htmlDeLaFicha($this, $user);

    // `toContain()` de Pest es variádico: no admite un segundo argumento de
    // mensaje, se interpretaría como otro needle. Para nombrar el campo que
    // falla se comprueba la contención por separado.
    $esperado = [
        'value="12"' => 'equipos[cantidad]',
        'value="Linux Mint"' => 'equipos[detalle]',
        'value="Faltan monitores"' => 'equipos[observaciones]',
        'value="Diésel 5kVA"' => 'energias[detalle]',
        'value="Combustible inestable"' => 'energias[observaciones]',
        'value="18"' => 'actividades[participantes]',
        'value="Taller de sumo"' => 'actividades[detalle]',
    ];

    $ausentes = [];

    foreach ($esperado as $valor => $campo) {
        if (! str_contains($html, $valor)) {
            $ausentes[] = $campo;
        }
    }

    expect($ausentes)->toBe([], 'Campos que no se repoblaron: '.implode(', ', $ausentes));
});
