<?php

use App\Enums\DimensionPerfilamientoEnum;
use App\Enums\TipoEquipoEnum;
use App\Models\DocumentoAnexo;
use App\Models\Infocentro;
use App\Support\VaciarFicha;
use Database\Factories\ActividadFactory;
use Database\Factories\AdminFactory;
use Database\Factories\DocumentoAnexoFactory;
use Database\Factories\EnergiaFactory;
use Database\Factories\FacilitadorFactory;
use Database\Factories\InfocentroUserFactory;
use Database\Factories\VinculacionMincytFactory;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Un infocentro con la ficha guardada de verdad, por el PUT de siempre.
 *
 * Las puntuaciones van en el payload porque el controller reescribe la columna
 * con lo que llega en el guardado: ponerlas antes no serviría de nada.
 *
 * @param  array<string, mixed>  $atributos
 */
function infocentroConFichaGuardada(array $atributos = [], array $extra = []): Infocentro
{
    $infocentro = Infocentro::factory()->create($atributos);

    InfocentroUserFactory::new()->forInfocentro($infocentro)->create();

    test()->actingAs($infocentro->user)->put('/infocentro/diagnostico', [
        'nombre' => $infocentro->nombre,
        'estado_id' => $infocentro->estado_id,
        'activo' => '1',
        ...$extra,
    ])->assertSessionHasNoErrors();

    return $infocentro->refresh();
}

/**
 * @return array<string, string>
 */
function lasSieteDimensiones(): array
{
    return collect(DimensionPerfilamientoEnum::cases())
        ->mapWithKeys(fn ($dimension): array => [$dimension->value => '2'])
        ->all();
}

test('eliminar la ficha la vacía y devuelve el flag a pendiente', function () {
    $admin = AdminFactory::new()->create();
    $infocentro = infocentroConFichaGuardada(['direccion' => 'Calle 1', 'telefono' => '04120000000']);

    expect($infocentro->diagnostico_guardado_at)->not->toBeNull();

    $this->actingAs($admin)
        ->delete("/admin/fichas/{$infocentro->id}")
        ->assertRedirect(route('admin.fichas.index'));

    $infocentro->refresh();

    expect($infocentro->diagnostico_guardado_at)->toBeNull()
        ->and($infocentro->diagnostico_actualizado_at)->toBeNull()
        ->and($infocentro->direccion)->toBeNull()
        ->and($infocentro->telefono)->toBeNull()
        ->and($infocentro->puntuaciones)->toBeNull()
        ->and($infocentro->puntuacion_total)->toBeNull()
        ->and($infocentro->tipo_infocentro)->toBeNull()
        ->and($infocentro->firmas)->toBeNull();
});

test('eliminar la ficha conserva lo que identifica al registro', function () {
    $admin = AdminFactory::new()->create();
    $infocentro = infocentroConFichaGuardada();

    $nombre = $infocentro->nombre;
    $codigo = $infocentro->codigo;
    $activo = $infocentro->activo;

    $this->actingAs($admin)->delete("/admin/fichas/{$infocentro->id}")->assertRedirect();

    $infocentro->refresh();

    // El infocentro no se borra: sigue en el catálogo para volver a capturarlo.
    expect(Infocentro::find($infocentro->id))->not->toBeNull()
        ->and($infocentro->nombre)->toBe($nombre)
        ->and($infocentro->codigo)->toBe($codigo)
        ->and($infocentro->activo)->toBe($activo);
});

test('las respuestas de Sí/No vuelven a no declarado, no a nulo', function () {
    $admin = AdminFactory::new()->create();
    $infocentro = infocentroConFichaGuardada(extra: ['tiene_facilitador_titular' => '1']);

    expect($infocentro->tiene_facilitador_titular)->toBeTrue();

    $this->actingAs($admin)->delete("/admin/fichas/{$infocentro->id}");

    $infocentro->refresh();

    // Son NOT NULL, así que a null el borrado reventaría. False es su estado
    // de «no declarado», que es lo que se quiere.
    expect($infocentro->tiene_facilitador_titular)->toBeFalse()
        ->and($infocentro->vinculacion_autoridad)->toBeFalse()
        ->and($infocentro->vinculacion_mincyt)->toBeFalse()
        ->and($infocentro->acomp_fundacite)->toBeFalse()
        ->and($infocentro->ciencia_ciudadana)->toBeFalse();
});

test('eliminar la ficha borra las filas de todas las secciones', function () {
    $admin = AdminFactory::new()->create();
    $infocentro = infocentroConFichaGuardada(extra: ['puntuaciones' => lasSieteDimensiones()]);

    FacilitadorFactory::new()->for($infocentro)->create();
    $infocentro->equipos()->create(['tipo' => TipoEquipoEnum::Computadoras, 'cantidad' => 3]);
    EnergiaFactory::new()->for($infocentro)->create();
    ActividadFactory::new()->for($infocentro)->create();
    VinculacionMincytFactory::new()->for($infocentro)->create();

    expect($infocentro->facilitadores()->count())->toBe(1)
        ->and($infocentro->equipos()->count())->toBe(1)
        ->and($infocentro->energias()->count())->toBe(1)
        ->and($infocentro->actividades()->count())->toBe(1)
        ->and($infocentro->vinculacionesMincyt()->count())->toBe(1);

    $this->actingAs($admin)->delete("/admin/fichas/{$infocentro->id}");

    $infocentro->refresh();

    expect($infocentro->facilitadores()->count())->toBe(0)
        ->and($infocentro->equipos()->count())->toBe(0)
        ->and($infocentro->energias()->count())->toBe(0)
        ->and($infocentro->actividades()->count())->toBe(0)
        ->and($infocentro->vinculacionesMincyt()->count())->toBe(0);
});

test('eliminar la ficha borra los documentos y sus archivos', function () {
    Storage::fake(config('filesystems.default'));

    $admin = AdminFactory::new()->create();
    $infocentro = infocentroConFichaGuardada();

    $ruta = "diagnostico/{$infocentro->id}/plan.pdf";
    Storage::disk(config('filesystems.default'))->put($ruta, 'contenido');

    DocumentoAnexoFactory::new()->for($infocentro)->create(['ruta' => $ruta]);

    expect(Storage::disk(config('filesystems.default'))->exists($ruta))->toBeTrue();

    $this->actingAs($admin)->delete("/admin/fichas/{$infocentro->id}");

    // El archivo se va con su fila: si no, quedaría ocupando espacio sin forma
    // de descargarlo.
    expect(DocumentoAnexo::where('infocentro_id', $infocentro->id)->count())->toBe(0)
        ->and(Storage::disk(config('filesystems.default'))->exists($ruta))->toBeFalse();
});

test('eliminar una ficha no toca la de otro infocentro', function () {
    $admin = AdminFactory::new()->create();
    $primero = infocentroConFichaGuardada(extra: ['telefono' => '04120000001']);
    $segundo = infocentroConFichaGuardada(extra: ['telefono' => '04120000002']);

    $segundo->equipos()->create(['tipo' => TipoEquipoEnum::Computadoras, 'cantidad' => 5]);

    $this->actingAs($admin)->delete("/admin/fichas/{$primero->id}");

    expect($primero->refresh()->telefono)->toBeNull()
        ->and($segundo->refresh()->telefono)->toBe('04120000002')
        ->and($segundo->diagnostico_guardado_at)->not->toBeNull()
        ->and($segundo->equipos()->count())->toBe(1);
});

test('no se puede vaciar un infocentro que no tiene ficha', function () {
    $admin = AdminFactory::new()->create();
    $infocentro = Infocentro::factory()->create(['direccion' => 'Calle 1', 'telefono' => '04120000000']);

    // El botón solo se pinta en filas de fichas guardadas, pero la ruta se
    // puede escribir a mano. Sin esta barrera, vaciar un registro del catálogo
    // le perdería la dirección y el teléfono que gestiona el panel, sin haber
    // tenido nunca una ficha que borrar.
    $this->actingAs($admin)
        ->get("/admin/fichas/{$infocentro->id}/eliminar")
        ->assertNotFound();

    $this->actingAs($admin)
        ->delete("/admin/fichas/{$infocentro->id}")
        ->assertNotFound();

    expect($infocentro->refresh()->direccion)->toBe('Calle 1')
        ->and($infocentro->telefono)->toBe('04120000000');
});

test('solo un administrador puede eliminar una ficha', function () {
    $infocentro = infocentroConFichaGuardada();

    // El facilitador es dueño de la ficha pero no del catálogo: desde su sesión
    // no existe esta puerta.
    $this->actingAs($infocentro->user)
        ->delete("/admin/fichas/{$infocentro->id}")
        ->assertForbidden();

    expect($infocentro->refresh()->diagnostico_guardado_at)->not->toBeNull();
});

test('la confirmación es la única barrera y enseña lo que se va a perder', function () {
    $admin = AdminFactory::new()->create();
    $infocentro = infocentroConFichaGuardada(extra: ['puntuaciones' => lasSieteDimensiones()]);
    FacilitadorFactory::new()->for($infocentro)->create();
    $infocentro->equipos()->create(['tipo' => TipoEquipoEnum::Computadoras, 'cantidad' => 3]);
    $infocentro->equipos()->create(['tipo' => TipoEquipoEnum::TelescopiosPortatiles, 'cantidad' => 2]);

    $this->actingAs($admin)
        ->get("/admin/fichas/{$infocentro->id}/eliminar")
        ->assertOk()
        ->assertSee($infocentro->codigo)
        ->assertSee($infocentro->nombre)
        // Que se vea cuántas cosas son, no solo el nombre del infocentro.
        ->assertSee('Facilitadores')
        ->assertSee('Equipos')
        ->assertSee('No se puede deshacer');

    // Ygetting there has not touched anything yet.
    expect($infocentro->refresh()->diagnostico_guardado_at)->not->toBeNull()
        ->and($infocentro->equipos()->count())->toBe(2);
});

test('el aviso dice qué infocentro se ha devuelto a pendientes', function () {
    $admin = AdminFactory::new()->create();
    $infocentro = infocentroConFichaGuardada();

    $this->actingAs($admin)
        ->delete("/admin/fichas/{$infocentro->id}")
        ->assertSessionHas('status');
    expect(session('status'))
        ->toContain($infocentro->codigo);
});

test('la ficha eliminada desaparece del módulo de fichas', function () {
    $admin = AdminFactory::new()->create();
    $infocentro = infocentroConFichaGuardada();

    $this->actingAs($admin)->get('/admin/fichas')->assertSee($infocentro->nombre);

    $this->actingAs($admin)->delete("/admin/fichas/{$infocentro->id}");

    // Ya no tiene ficha, así que ya no aparece entre las capturadas y el botón
    // de eliminar desaparece con ella.
    $this->actingAs($admin)
        ->get('/admin/fichas')
        ->assertDontSee($infocentro->nombre)
        ->assertDontSee(route('admin.fichas.eliminar', $infocentro));

    // Pero sí vuelve a la lista de pendientes del catálogo.
    $this->actingAs($admin)->get('/admin/infocentros?ficha=pendiente')->assertSee($infocentro->nombre);
});

test('el botón de eliminar aparece en la fila del módulo de fichas', function () {
    $admin = AdminFactory::new()->create();
    $infocentro = infocentroConFichaGuardada();

    $this->actingAs($admin)
        ->get('/admin/fichas')
        ->assertSee(route('admin.fichas.eliminar', $infocentro));
});

test('el borrado no intenta vaciar ninguna columna que el esquema no permita', function () {
    $obligatorias = collect(Schema::getColumns('infocentros'))
        ->reject(fn (array $columna): bool => $columna['nullable'])
        ->pluck('name');

    $aVaciar = app(VaciarFicha::class)->campos();

    // Una columna obligatoria no puede quedar a null. Las cinco respuestas de
    // Sí/No sí son obligatorias y sí se vacían, pero a false. Si alguien añade
    // una columna NOT NULL a la ficha y no la declara ni como identidad ni como
    // respuesta de Sí/No, aquí caería a null y el borrado reventaría contra la
    // restricción: en producción, con trabajo de campo real detrás.
    $enNull = collect($aVaciar)
        ->filter(fn ($valor, string $columna): bool => $obligatorias->contains($columna) && $valor !== false);

    expect($enNull->keys()->all())->toBeEmpty();
});

test('la identidad del registro queda fuera de lo que se vacía', function () {
    $aVaciar = array_keys(app(VaciarFicha::class)->campos());

    expect($aVaciar)->not->toContain('codigo', 'nombre', 'activo');
});

test('todo lo que se vacía es una columna de verdad', function () {
    // Un nombre mal escrito en la constante se llevaría por delante una fila que
    // no existe, y el borrado reventaría en la primera ficha de producción.
    foreach (array_keys(app(VaciarFicha::class)->campos()) as $columna) {
        expect(Schema::hasColumn('infocentros', $columna))->toBeTrue();
    }
});

test('el motivo de cierre se va con la ficha', function () {
    // Documenta una consecuencia que puede sorprender: el infocentro sigue
    // inactivo, pero pierde el motivo. Quien rellene la ficha de nuevo tendrá
    // que volver a declararlo.
    $admin = AdminFactory::new()->create();
    $infocentro = infocentroConFichaGuardada(
        ['activo' => false],
        ['activo' => '0', 'razon_cierre' => 'Sede closed'],
    );

    expect($infocentro->razon_cierre)->toBe('Sede closed');

    $this->actingAs($admin)->delete("/admin/fichas/{$infocentro->id}");

    $infocentro->refresh();

    expect($infocentro->activo)->toBeFalse()
        ->and($infocentro->razon_cierre)->toBeNull();
});

test('el botón sale en el listado de infocentros que tienen ficha', function () {
    $admin = AdminFactory::new()->create();
    $infocentro = infocentroConFichaGuardada();

    $this->actingAs($admin)
        ->get('/admin/infocentros')
        ->assertSee("/admin/fichas/{$infocentro->id}/eliminar?origen=infocentros", false);
});

test('el botón no sale en las filas que no tienen ficha', function () {
    $admin = AdminFactory::new()->create();
    $sinFicha = Infocentro::factory()->create(['nombre' => 'Infocentro sin ficha de prueba']);

    // Un botón que lleva a un 404 es peor que no tener botón: la ruta exige que
    // exista la ficha precisamente para que no se pueda vaciar un registro del
    // catálogo que nunca la tuvo.
    $this->actingAs($admin)
        ->get('/admin/infocentros')
        ->assertSee('Infocentro sin ficha de prueba')
        ->assertDontSee("/admin/fichas/{$sinFicha->id}/eliminar", false);

    $conFicha = infocentroConFichaGuardada();

    // Y en la misma página, la que sí tiene ficha, sí lo muestra.
    $this->actingAs($admin)
        ->get('/admin/infocentros')
        ->assertSee("/admin/fichas/{$conFicha->id}/eliminar", false);
});

test('borrar desde el listado de infocentros devuelve al listado de infocentros', function () {
    $admin = AdminFactory::new()->create();
    $infocentro = infocentroConFichaGuardada();

    $this->actingAs($admin)
        ->delete("/admin/fichas/{$infocentro->id}?origen=infocentros")
        ->assertRedirect(route('admin.infocentros.index'));

    expect($infocentro->refresh()->diagnostico_guardado_at)->toBeNull();
});

test('al volver al listado de infocentros se conservan sus filtros', function () {
    $admin = AdminFactory::new()->create();
    $infocentro = infocentroConFichaGuardada();

    // Sin esto, limpiar cinco fichas seguidas con `?ficha=hecha` tiraría el
    // filtro y la búsqueda en cada borrado.
    $this->actingAs($admin)
        ->delete("/admin/fichas/{$infocentro->id}?origen=infocentros&q=muestra&ficha=hecha&page=2")
        ->assertRedirect(route('admin.infocentros.index', ['q' => 'muestra', 'ficha' => 'hecha', 'page' => 2]));
});

test('borrar desde el módulo de fichas sigue volviendo al módulo de fichas', function () {
    $admin = AdminFactory::new()->create();
    $infocentro = infocentroConFichaGuardada();

    $this->actingAs($admin)
        ->delete("/admin/fichas/{$infocentro->id}")
        ->assertRedirect(route('admin.fichas.index'));
});

test('los filtros del módulo de fichas también sobreviven', function () {
    $admin = AdminFactory::new()->create();
    $infocentro = infocentroConFichaGuardada(extra: ['puntuaciones' => lasSieteDimensiones()]);

    $this->actingAs($admin)
        ->delete("/admin/fichas/{$infocentro->id}?tipo=a&prioridad=alta&q=muestra")
        ->assertRedirect(route('admin.fichas.index', ['q' => 'muestra', 'tipo' => 'a', 'prioridad' => 'alta']));
});

test('un origen desconocido cae al módulo de fichas y no rompe el redirect', function () {
    $admin = AdminFactory::new()->create();
    $infocentro = infocentroConFichaGuardada();

    // No se puede producir un open redirect porque `redirect()` solo recibe
    // nombres de ruta de una lista fija, pero un valor inventado tampoco puede
    // dejar la redirección a medias.
    $this->actingAs($admin)
        ->delete('/admin/fichas/'.$infocentro->id.'?origen='.urlencode('https://otro-sitio.example'))
        ->assertRedirect(route('admin.fichas.index'));
});

test('la confirmación vuelve al listado del que se vino', function () {
    $admin = AdminFactory::new()->create();
    $infocentro = infocentroConFichaGuardada();

    $this->actingAs($admin)
        ->get("/admin/fichas/{$infocentro->id}/eliminar?origen=infocentros&ficha=hecha")
        ->assertOk()
        // El botón de cancelar es la salida de emergencia: tiene que devolver
        // al sitio de origen, con el filtro puesto.
        ->assertSee('/admin/infocentros?ficha=hecha', false)
        // El origen viaja en la URL del formulario, no en un campo oculto: los
        // ocultos los rellena el navegador con lo que autocompletó antes.
        // La URL se escribe a mano y se escapa como Blade la escapa en el HTML.
        ->assertSee(
            e("/admin/fichas/{$infocentro->id}?origen=infocentros&ficha=hecha"),
            false,
        );
});

test('el origen no viaja en un campo oculto que el navegador pueda rellenar', function () {
    $admin = AdminFactory::new()->create();
    $infocentro = infocentroConFichaGuardada();

    // Chrome rellena los campos ocultos con lo que autocompletó antes, y aquí lo
    // hizo: el botón de eliminar mandaba al módulo de fichas en vez de al
    // listado de origen. Va en la URL del formulario, que el navegador no toca.
    $this->actingAs($admin)
        ->get("/admin/fichas/{$infocentro->id}/eliminar?origen=infocentros")
        ->assertOk()
        ->assertDontSee('type="hidden" name="origen"', false);
});

/**
 * Saca de un HTML el enlace de borrado de una fila y lo devuelve troceado.
 *
 * Se parsea a mano en vez de reconstruir la URL con `route()`: si el test usa
 * la misma llamada que la vista, los dos se equivocan juntos y el guard pasa
 * con un enlace al que le faltan los filtros.
 *
 * @return array{url: string, query: array<string, string>}
 */
function enlaceDeBorrado(string $html, Infocentro $infocentro, string $selector): array
{
    // El `?` es lo que distingue estas URLs de la de «Ver la ficha», que va al
    // mismo id pero sin query. Con el `[^"]*` de en medio sirve para el enlace a
    // la confirmación, que lleva `/eliminar`, y para el action del formulario,
    // que no lo lleva.
    $aguja = '/admin/fichas/'.$infocentro->id;
    preg_match('#'.$selector.'="([^"]*'.preg_quote($aguja, '#').'[^"]*\?[^"]*)"#', $html, $encontrado);

    $url = html_entity_decode($encontrado[1] ?? '', ENT_QUOTES);
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

    return ['url' => $url, 'query' => array_map(strval(...), $query)];
}

test('los filtros del listado llegan a la confirmación', function () {
    $admin = AdminFactory::new()->create();
    // El nombre tiene que llevar «muestra» o la búsqueda del listado no lo
    // encuentra y el guard pasa sin comprobar nada.
    $infocentro = infocentroConFichaGuardada(['nombre' => 'Infocentro de muestra — Filtros']);

    // Se recorre el camino que de verdad recorre una persona: el enlace sale
    // del listado filtrado. Si el enlace no lleva los filtros, la confirmación
    // no tiene con qué devolver al listado con ellos, y limpiar cinco fichas
    // seguidas obliga a reponer el filtro cada vez.
    $listado = $this->actingAs($admin)->get('/admin/infocentros?ficha=hecha&q=muestra')->getContent();

    expect(enlaceDeBorrado($listado, $infocentro, 'href')['query'])
        ->toMatchArray(['origen' => 'infocentros', 'ficha' => 'hecha', 'q' => 'muestra']);

    $confirmacion = $this->actingAs($admin)
        ->get("/admin/fichas/{$infocentro->id}/eliminar?origen=infocentros&ficha=hecha&q=muestra")
        ->getContent();

    expect(enlaceDeBorrado($confirmacion, $infocentro, 'action')['query'])
        ->toMatchArray(['origen' => 'infocentros', 'ficha' => 'hecha', 'q' => 'muestra']);
});

test('los filtros del módulo de fichas llegan a la confirmación', function () {
    $admin = AdminFactory::new()->create();
    // Con las siete a 3 el total es 21 y sale tipo A, que es lo que pide el
    // filtro: con un 2 en todas serían 14 y la lista salía vacía.
    $infocentro = infocentroConFichaGuardada(
        ['nombre' => 'Infocentro de muestra — Módulo'],
        ['puntuaciones' => collect(DimensionPerfilamientoEnum::cases())
            ->mapWithKeys(fn ($d): array => [$d->value => '3'])
            ->all()],
    );

    $listado = $this->actingAs($admin)->get('/admin/fichas?tipo=a&q=muestra')->getContent();

    expect(enlaceDeBorrado($listado, $infocentro, 'href')['query'])
        ->toMatchArray(['origen' => 'fichas', 'tipo' => 'a', 'q' => 'muestra']);
});

test('la confirmación devuelve al listado con sus filtros', function () {
    $admin = AdminFactory::new()->create();
    $infocentro = infocentroConFichaGuardada(['nombre' => 'Infocentro de muestra — Cancelar']);

    $confirmacion = $this->actingAs($admin)
        ->get("/admin/fichas/{$infocentro->id}/eliminar?origen=infocentros&ficha=hecha&q=muestra")
        ->getContent();

    // Cancelar es la salida de emergencia: tiene que devolver al sitio de
    // origen y no perder el filtro.
    preg_match('#href="([^"]*\/admin\/infocentros\?[^"]*)"#', $confirmacion, $cancelar);
    parse_str((string) parse_url(html_entity_decode($cancelar[1] ?? ''), PHP_URL_QUERY), $query);

    expect($query)->toMatchArray(['ficha' => 'hecha', 'q' => 'muestra']);
});

test('los enlaces sin filtros no cuelgan parámetros vacíos', function () {
    $admin = AdminFactory::new()->create();
    $infocentro = infocentroConFichaGuardada(['nombre' => 'Infocentro de muestra — Sin filtros']);

    $listado = $this->actingAs($admin)->get('/admin/fichas')->getContent();
    $query = enlaceDeBorrado($listado, $infocentro, 'href')['query'];

    expect($query['origen'])->toBe('fichas')
        ->and(array_filter($query, fn (string $valor): bool => $valor === ''))->toBeEmpty();
});
