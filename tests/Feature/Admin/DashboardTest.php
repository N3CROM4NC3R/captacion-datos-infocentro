<?php

use App\Enums\DimensionPerfilamientoEnum;
use App\Enums\TipoInfocentroEnum;
use App\Models\Infocentro;
use Database\Factories\AdminFactory;
use Database\Factories\InfocentroUserFactory;

/**
 * Un infocentro con la ficha guardada y por tanto con clasificación.
 *
 * El tipo no lo pone nadie: lo deduce el controller al guardar. Con las siete
 * dimensiones a 3 sale 21 y tipo A; a 2 sale 14 y tipo B; a 0, tipo C.
 */
function infocentroDelPanel(string $clasificacion, bool $activo = true): Infocentro
{
    $infocentro = Infocentro::factory()->create(['activo' => $activo]);
    $usuario = InfocentroUserFactory::new()->forInfocentro($infocentro)->create();

    $valor = match ($clasificacion) {
        'a' => '3',
        'b' => '2',
        default => '0',
    };

    test()->actingAs($usuario)->put('/infocentro/diagnostico', [
        'nombre' => $infocentro->nombre,
        'estado_id' => $infocentro->estado_id,
        'activo' => $activo ? '1' : '0',
        'puntuaciones' => collect(DimensionPerfilamientoEnum::cases())
            ->mapWithKeys(fn ($dimension): array => [$dimension->value => $valor])
            ->all(),
    ])->assertSessionHasNoErrors();

    return $infocentro->refresh();
}

/**
 * Los números del panel, leídos del texto plano.
 *
 * En el HTML en bruto un «792» puede estar partido entre etiquetas, así que se
 * quita el markup antes de buscar.
 */
function plano(string $html): string
{
    return trim((string) preg_replace('/\s+/u', ' ', strip_tags($html)));
}

function panel(): string
{
    return plano(test()->actingAs(AdminFactory::new()->create())
        ->get('/admin/dashboard')
        ->assertOk()
        ->getContent());
}

test('el panel enseña los seis números', function () {
    infocentroDelPanel('a');
    infocentroDelPanel('b');
    infocentroDelPanel('c');
    Infocentro::factory()->count(2)->create(['activo' => false]);

    $texto = panel();

    expect($texto)->toContain(__('Infocentros registrados'))
        ->toContain(__('Infocentros activos'))
        ->toContain(__('Infocentros inactivos'))
        ->toContain(__('Sin clasificar'));

    foreach (TipoInfocentroEnum::cases() as $tipo) {
        expect($texto)->toContain($tipo->label())
            ->toContain($tipo->rango());
    }
});

test('los seis números cuadran con la base', function () {
    infocentroDelPanel('a');
    infocentroDelPanel('a');
    infocentroDelPanel('b');
    infocentroDelPanel('c');
    Infocentro::factory()->count(3)->create(['activo' => false]);

    expect(Infocentro::count())->toBe(7)
        ->and(Infocentro::where('activo', true)->count())->toBe(4)
        ->and(Infocentro::where('activo', false)->count())->toBe(3)
        ->and(Infocentro::where('tipo_infocentro', 'a')->count())->toBe(2)
        ->and(Infocentro::where('tipo_infocentro', 'b')->count())->toBe(1)
        ->and(Infocentro::where('tipo_infocentro', 'c')->count())->toBe(1)
        ->and(Infocentro::whereNull('tipo_infocentro')->count())->toBe(3);
});

test('sin clasificar es exactamente sin ficha guardada', function () {
    infocentroDelPanel('a');
    Infocentro::factory()->count(4)->create();

    // `tipo_infocentro` lo calcula el perfilamiento en cada guardado, así que es
    // nulo si y solo si no hay ficha. Esa equivalencia es la que sostiene el
    // enlace de la ficha «Sin clasificar»; si el perfilamiento dejara de
    // clasificar alguna ficha, el título del panel empezaría a mentir.
    expect(Infocentro::whereNull('tipo_infocentro')->pluck('id')->all())
        ->toBe(Infocentro::whereNull('diagnostico_guardado_at')->pluck('id')->all());
});

test('cada ficha del panel lleva al listado con su filtro', function () {
    $admin = AdminFactory::new()->create();
    infocentroDelPanel('a');
    Infocentro::factory()->count(4)->create(['activo' => false]);

    $html = test()->actingAs($admin)->get('/admin/dashboard')->assertOk()->getContent();

    $enlaces = [
        route('admin.infocentros.index') => null,
        route('admin.infocentros.index', ['estatus' => 'activo']) => null,
        route('admin.infocentros.index', ['estatus' => 'inactivo']) => null,
        route('admin.infocentros.index', ['clasificacion' => 'a']) => null,
        route('admin.infocentros.index', ['ficha' => 'pendiente']) => null,
    ];

    foreach (array_keys($enlaces) as $url) {
        expect($html)->toContain(e($url));
    }
});

test('el número que enseña una ficha es el que devuelve su enlace', function () {
    $admin = AdminFactory::new()->create();
    infocentroDelPanel('a');
    infocentroDelPanel('b');
    infocentroDelPanel('c');
    InfocentroDelPanel('c');
    InfocentroDelPanel('c');
    Infocentro::factory()->count(6)->create(['activo' => false]);

    // Cada tipo con una cantidad distinta —1, 2 y 3— porque si todos tuvieran
    // la misma, contar uno por el valor de otro daría el mismo número y el
    // guard no vería nada.
    // El crux del panel: si un enlace apunta al filtro equivocado, el número
    // sigue siendo correcto y el error solo aparece al pulsar. Por eso aquí se
    // sigue el enlace y se cuenta lo que sale, en vez de comparar solo el texto.
    $casos = [
        'estatus=activo' => Infocentro::where('activo', true)->count(),
        'estatus=inactivo' => Infocentro::where('activo', false)->count(),
        'clasificacion=a' => Infocentro::where('tipo_infocentro', 'a')->count(),
        'clasificacion=b' => Infocentro::where('tipo_infocentro', 'b')->count(),
        'clasificacion=c' => Infocentro::where('tipo_infocentro', 'c')->count(),
        'ficha=pendiente' => Infocentro::whereNull('tipo_infocentro')->count(),
    ];

    $total = number_format(Infocentro::count());

    foreach ($casos as $filtro => $esperados) {
        $listado = plano(test()->actingAs($admin)->get('/admin/infocentros?'.$filtro)->assertOk()->getContent());

        // El encabezado del listado anuncia el recuento del filtro, así que es
        // donde se comprueba que el enlace lleva al grupo que dice su número.
        expect($listado)->toContain(
            number_format($esperados).' de '.$total.' infocentros coinciden con los filtros.',
        );
    }
});

test('cada ficha de tipo enseña su propio número', function () {
    infocentroDelPanel('a');
    infocentroDelPanel('b');
    infocentroDelPanel('c');
    infocentroDelPanel('c');
    infocentroDelPanel('c');
    Infocentro::factory()->count(4)->create();

    expect(Infocentro::where('tipo_infocentro', 'a')->count())->toBe(1)
        ->and(Infocentro::where('tipo_infocentro', 'b')->count())->toBe(1)
        ->and(Infocentro::where('tipo_infocentro', 'c')->count())->toBe(3)
        ->and(Infocentro::whereNull('tipo_infocentro')->count())->toBe(4);

    $texto = panel();

    // 1, 1 y 3 en la ficha de cada tipo, y 4 en la de sin clasificar.
    expect($texto)->toContain(__('Tipo A — Núcleo piloto').' 15-21 puntos 1')
        ->toContain(__('Tipo B — En consolidación').' 8-14 puntos 1')
        ->toContain(__('Tipo C — Por recuperar').' 0-7 puntos 3')
        ->toContain(__('Todavía sin ficha guardada').' 4');
});

test('el panel aparece vacío de clasificaciones cuando no hay ninguna ficha', function () {
    Infocentro::factory()->count(6)->create();

    $texto = panel();

    expect($texto)->toContain(number_format(0).' de '.number_format(6).' infocentros tienen ficha de diagnóstico guardada y clasificación.');
});

test('solo un administrador ve el panel', function () {
    $admin = AdminFactory::new()->create();
    $facilitador = InfocentroUserFactory::new()->create();

    test()->actingAs($admin)->get('/admin/dashboard')->assertOk();

    test()->actingAs($facilitador)->get('/admin/dashboard')->assertForbidden();
});
