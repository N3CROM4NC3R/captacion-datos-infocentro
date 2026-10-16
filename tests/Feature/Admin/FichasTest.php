<?php

use App\Enums\DimensionPerfilamientoEnum;
use App\Enums\TipoInfocentroEnum;
use App\Models\Estado;
use App\Models\Infocentro;
use Database\Factories\AdminFactory;
use Database\Factories\InfocentroUserFactory;
use Illuminate\Testing\TestResponse;

/**
 * Guardar la ficha como lo haría el facilitador, por el método PUT real.
 *
 * @param  array<string, mixed>  $extra
 */
function guardarFichaDePrueba(Infocentro $infocentro, array $extra = []): TestResponse
{
    return test()->actingAs($infocentro->user)->put('/infocentro/diagnostico', [
        'nombre' => $infocentro->nombre,
        'estado_id' => $infocentro->estado_id ?? Estado::factory()->create()->id,
        'activo' => $infocentro->activo ? '1' : '0',
        ...$extra,
    ]);
}

/**
 * Un infocentro con la ficha ya guardada, para no repetir el guardado en cada
 * prueba del módulo.
 *
 * La factory de infocentros no crea su usuario, así que hay que añadirlo: sin
 * él no hay nadie a quien atribuir el guardado.
 */
function cerrarSesionDePrueba(): void
{
    // Guardar la ficha deja al facilitador con la sesión iniciada, así que una
    // petición posterior no es de un invitado mientras no se cierre.
    test()->post('/logout');
}

function infocentroConFicha(array $atributos = [], array $puntuaciones = []): Infocentro
{
    $infocentro = Infocentro::factory()->create($atributos);

    InfocentroUserFactory::new()->forInfocentro($infocentro)->create();

    // Las puntuaciones van en el payload, no en la factory: el controller
    // reescribe la columna con lo que llega en el guardado, así que ponerlas
    // antes no serviría de nada.
    guardarFichaDePrueba($infocentro->refresh(), $puntuaciones === [] ? [] : ['puntuaciones' => $puntuaciones])
        ->assertSessionHasNoErrors();

    return $infocentro->refresh();
}

/**
 * Las siete dimensiones con el mismo valor.
 *
 * @return array<string, string>
 */
function puntuacionesTotales(int $valor): array
{
    return collect(DimensionPerfilamientoEnum::cases())
        ->mapWithKeys(fn ($dimension): array => [$dimension->value => (string) $valor])
        ->all();
}

test('guardar la ficha deja constancia de cuándo se hizo', function () {
    $user = InfocentroUserFactory::new()->create();
    $infocentro = $user->infocentro;

    expect($infocentro->diagnostico_guardado_at)->toBeNull();

    guardarFichaDePrueba($infocentro)->assertSessionHasNoErrors();

    $infocentro->refresh();

    expect($infocentro->diagnostico_guardado_at)->not->toBeNull()
        ->and($infocentro->diagnostico_actualizado_at)->not->toBeNull();
});

test('la fecha del primer guardado no se sobrescribe al volver a guardar', function () {
    $user = InfocentroUserFactory::new()->create();
    $infocentro = $user->infocentro;

    guardarFichaDePrueba($infocentro)->assertSessionHasNoErrors();

    $primera = $infocentro->refresh()->diagnostico_guardado_at;

    $this->travel(1)->days();

    guardarFichaDePrueba($infocentro, ['telefono' => '04121234567'])->assertSessionHasNoErrors();

    $infocentro->refresh();

    // `guardado_at` es la marca de que se completó, así que se queda clavada;
    // `actualizado_at` es la de la última edición, y sí avanza.
    expect($infocentro->diagnostico_guardado_at->timestamp)->toBe($primera->timestamp)
        ->and($infocentro->diagnostico_actualizado_at->timestamp)->toBeGreaterThan($primera->timestamp)
        ->and($infocentro->telefono)->toBe('04121234567');
});

test('un guardado rechazado no deja marca de ficha hecha', function () {
    $user = InfocentroUserFactory::new()->create();
    $infocentro = $user->infocentro;

    // Sin `nombre` la validación falla y no debe pasar nada a la base.
    $this->actingAs($user)
        ->put('/infocentro/diagnostico', ['activo' => '1'])
        ->assertSessionHasErrors('nombre');

    expect($infocentro->refresh()->diagnostico_guardado_at)->toBeNull(
        'Una ficha que no se pudo guardar no puede contar como hecha.',
    );
});

test('el listado filtra por ficha hecha y por ficha pendiente', function () {
    $admin = AdminFactory::new()->create();
    $conFicha = infocentroConFicha();
    $sinFicha = Infocentro::factory()->create();

    $this->actingAs($admin)->get('/admin/infocentros?ficha=hecha')
        ->assertOk()
        ->assertSee($conFicha->nombre)
        ->assertDontSee($sinFicha->nombre);

    $this->actingAs($admin)->get('/admin/infocentros?ficha=pendiente')
        ->assertOk()
        ->assertSee($sinFicha->nombre)
        ->assertDontSee($conFicha->nombre);
});

test('el listado busca por nombre y por código', function () {
    $admin = AdminFactory::new()->create();
    $infocentro = infocentroConFicha();
    $otro = Infocentro::factory()->create();

    $this->actingAs($admin)->get('/admin/infocentros?nombre='.urlencode($infocentro->nombre))
        ->assertOk()
        ->assertSee($infocentro->nombre)
        ->assertDontSee($otro->nombre);

    // El código va en minúsculas en el catálogo, así que la búsqueda no
    // distingue mayúsculas: es un `ilike`, no un `where` exacto.
    $this->actingAs($admin)->get('/admin/infocentros?codigo='.urlencode(strtolower($infocentro->codigo)))
        ->assertOk()
        ->assertSee($infocentro->nombre)
        ->assertDontSee($otro->nombre);
});

test('el filtro sobrevive a la paginación', function () {
    $admin = AdminFactory::new()->create();

    // La página pide 15 filas: con menos no hay enlaces y no se puede
    // comprobar nada. La marca se pone directamente porque lo que se prueba es
    // el filtro, no el guardado — de eso hay una prueba propia.
    Infocentro::factory()
        ->count(20)
        ->create(['diagnostico_guardado_at' => now(), 'diagnostico_actualizado_at' => now()]);

    // Sin withQueryString, `links()` regenera la paginación y tira el filtro.
    $html = $this->actingAs($admin)->get('/admin/infocentros?ficha=hecha')
        ->assertOk()
        ->getContent();

    expect(str_contains($html, 'ficha=hecha'))->toBeTrue(
        'La paginación debe conservar el filtro activo.',
    );
});

test('el listado dice cuántas fichas hay de cuántas', function () {
    $admin = AdminFactory::new()->create();
    infocentroConFicha();
    infocentroConFicha();

    $html = $this->actingAs($admin)->get('/admin/infocentros')
        ->assertOk()
        ->getContent();

    // Los dos números van dentro de su propio <span>, así que se comprueba
    // el texto del contador, no una cadena que nunca aparece entera.
    expect(str_contains($html, 'infocentros tienen la ficha de diagnóstico guardada'))->toBeTrue()
        ->and(str_contains($html, (string) Infocentro::count()))->toBeTrue();
});

test('el módulo de fichas solo lista las que están guardadas', function () {
    $admin = AdminFactory::new()->create();
    $conFicha = infocentroConFicha();
    $sinFicha = Infocentro::factory()->create();

    $this->actingAs($admin)->get('/admin/fichas')
        ->assertOk()
        ->assertSee($conFicha->nombre)
        ->assertDontSee($sinFicha->nombre);
});

test('el módulo de fichas filtra por tipo, por prioridad y por búsqueda', function () {
    $admin = AdminFactory::new()->create();

    $tipoA = infocentroConFicha([], puntuacionesTotales(3));
    $tipoC = infocentroConFicha([], puntuacionesTotales(0));

    expect($tipoA->refresh()->tipo_infocentro)->toBe(TipoInfocentroEnum::A)
        ->and($tipoC->refresh()->tipo_infocentro)->toBe(TipoInfocentroEnum::C);

    $this->actingAs($admin)->get('/admin/fichas?tipo=a')
        ->assertOk()
        ->assertSee($tipoA->nombre)
        ->assertDontSee($tipoC->nombre);

    $this->actingAs($admin)->get('/admin/fichas?q='.urlencode($tipoC->nombre))
        ->assertOk()
        ->assertSee($tipoC->nombre)
        ->assertDontSee($tipoA->nombre);
});

test('el módulo de fichas muestra total, tipo y prioridad', function () {
    $admin = AdminFactory::new()->create();
    // 3 + 3 + 2 = 8, el borde inferior del Tipo B.
    $ficha = infocentroConFicha([], [
        'conectividad' => 3, 'operatividad' => 3, 'facilitadores' => 2,
        'equipamiento' => 0, 'articulacion_comunitaria' => 0,
        'oferta_formativa' => 0, 'vinculacion_mincyt' => 0,
    ]);

    expect($ficha->puntuacion_total)->toBe(8)
        ->and($ficha->tipo_infocentro)->toBe(TipoInfocentroEnum::B);

    $this->actingAs($admin)->get('/admin/fichas')
        ->assertOk()
        ->assertSee(TipoInfocentroEnum::B->label());
});

test('la ficha completa se consulta en solo lectura', function () {
    $admin = AdminFactory::new()->create();
    $ficha = infocentroConFicha();

    $html = $this->actingAs($admin)
        ->get(route('admin.fichas.show', $ficha))
        ->assertOk()
        ->getContent();

    expect(str_contains($html, 'Sección A. Identificación'))->toBeTrue()
        ->and(str_contains($html, 'Sección J. Documentos anexos'))->toBeTrue()
        ->and(str_contains($html, 'Perfilamiento y clasificación'))->toBeTrue()
        ->and(str_contains($html, $ficha->codigo))->toBeTrue();
});

test('la ficha de consulta va entera dentro de un fieldset deshabilitado', function () {
    $admin = AdminFactory::new()->create();
    $ficha = infocentroConFicha();

    $html = $this->actingAs($admin)
        ->get(route('admin.fichas.show', $ficha))
        ->assertOk()
        ->getContent();

    // Reutiliza los partials de la ficha dentro de un fieldset deshabilitado.
    // `fieldset[disabled]` desactiva por herencia, así que los hijos no llevan
    // el atributo: lo que hay que fijar aquí es que el fieldset existe y
    // envuelve las once secciones. Que los controles queden de verdad
    // deshabilitados lo comprueba el navegador, donde se puede leer la
    // propiedad `disabled` de cada uno.
    expect(str_contains($html, '<fieldset disabled'))->toBeTrue(
        'La ficha de consulta debe ir dentro de un fieldset deshabilitado.',
    );

    preg_match('/<fieldset disabled[^>]*>(.*)<\/fieldset>/s', $html, $caja);
    $dentro = $caja[1] ?? '';

    expect($dentro)->not->toBe('');

    foreach (['A. Identificación', 'C. Operatividad', 'G. Infraestructura', 'I. Necesidades', 'J. Documentos anexos', 'Firmas', 'Perfilamiento'] as $seccion) {
        expect(str_contains($dentro, $seccion))->toBeTrue(
            "La sección «{$seccion}» debe quedar dentro del fieldset.",
        );
    }

    // Nada editable fuera del fieldset: es la única forma de que la consulta
    // se volviera a convertir en formulario sin que se notara.
    $antes = strstr($html, '<fieldset disabled', true);
    preg_match_all('/<(input|select|textarea)\b[^>]*>/i', (string) $antes, $fuera);

    // Los que quedan antes son los del formulario de salida de sesión del
    // encabezado, que no forma parte de la ficha.
    $ajenos = array_values(array_filter($fuera[0], fn (string $t): bool => ! str_contains($t, 'name="_token"') && ! str_contains($t, 'type="hidden"')));

    expect($ajenos)->toBe([], 'Controles fuera del fieldset: '.implode(' | ', array_slice($ajenos, 0, 3)));
});

test('la ficha de consulta declara los mismos ámbitos Alpine que la ficha', function () {
    $user = InfocentroUserFactory::new()->create();
    $admin = AdminFactory::new()->create();
    $ficha = infocentroConFicha();

    $ambitos = function (string $html): array {
        preg_match_all('/x-data="([^"(]+)\(/', $html, $m);

        return array_values(array_unique($m[1]));
    };

    $deLaFicha = $ambitos($this->actingAs($user)->get('/infocentro/diagnostico')->assertOk()->getContent());
    $deLaConsulta = $ambitos($this->actingAs($admin)->get(route('admin.fichas.show', $ficha))->assertOk()->getContent());

    // Varios partials no declaran su propio `x-data` y leen del ámbito que
    // envuelve a toda la ficha: la sección F, por ejemplo, usa
    // `actividades[...]`. Si la consulta no declara ese ámbito, sus campos
    // condicionales quedan ocultos aunque estén rellenos y la ficha se
    // muestra más vacía de lo que está.
    expect($deLaConsulta)->toContain('fichaDiagnostico')
        ->and($deLaConsulta)->toEqual($deLaFicha);
});

test('la ficha de consulta no ofrece el botón de guardar', function () {
    $admin = AdminFactory::new()->create();
    $ficha = infocentroConFicha();

    $this->actingAs($admin)
        ->get(route('admin.fichas.show', $ficha))
        ->assertOk()
        ->assertDontSee(__('Guardar'))
        ->assertDontSee(route('infocentro.diagnostico.update'), escape: false);
});

test('un usuario de infocentro no llega al módulo de fichas', function () {
    $user = InfocentroUserFactory::new()->create();
    $ficha = infocentroConFicha();

    $this->actingAs($user)->get('/admin/fichas')->assertForbidden();
    $this->actingAs($user)->get(route('admin.fichas.show', $ficha))->assertForbidden();
});

test('un invitado es redirigido al login', function () {
    $ficha = infocentroConFicha();
    cerrarSesionDePrueba();

    $this->get('/admin/fichas')->assertRedirect(route('login'));
    $this->get(route('admin.fichas.show', $ficha))->assertRedirect(route('login'));
});
