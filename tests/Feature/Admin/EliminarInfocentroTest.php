<?php

use App\Enums\TipoEquipoEnum;
use App\Models\Infocentro;
use App\Models\User;
use Database\Factories\ActividadFactory;
use Database\Factories\AdminFactory;
use Database\Factories\DocumentoAnexoFactory;
use Database\Factories\EnergiaFactory;
use Database\Factories\FacilitadorFactory;
use Database\Factories\InfocentroUserFactory;
use Database\Factories\UserFactory;
use Database\Factories\VinculacionMincytFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Un infocentro con ficha guardada, su cuenta y datos en las seis secciones.
 *
 * @param  array<string, mixed>  $atributos
 */
function infocentroCompleto(array $atributos = []): Infocentro
{
    $infocentro = Infocentro::factory()->create($atributos);
    $usuario = InfocentroUserFactory::new()->forInfocentro($infocentro)->create();

    test()->actingAs($usuario)->put('/infocentro/diagnostico', [
        'nombre' => $infocentro->nombre,
        'estado_id' => $infocentro->estado_id,
        'activo' => '1',
        'direccion' => 'Calle 1',
        'telefono' => '04120000000',
    ])->assertSessionHasNoErrors();

    FacilitadorFactory::new()->for($infocentro)->create();
    $infocentro->equipos()->create(['tipo' => TipoEquipoEnum::Computadoras, 'cantidad' => 3]);
    EnergiaFactory::new()->for($infocentro)->create();
    ActividadFactory::new()->for($infocentro)->create();
    VinculacionMincytFactory::new()->for($infocentro)->create();

    return $infocentro->refresh();
}

test('eliminar un infocentro lo borra del catálogo con su ficha', function () {
    $admin = AdminFactory::new()->create();
    $infocentro = infocentroCompleto();

    $this->actingAs($admin)
        ->delete("/admin/infocentros/{$infocentro->id}")
        ->assertRedirect(route('admin.infocentros.index'));

    expect(Infocentro::find($infocentro->id))->toBeNull()
        ->and($infocentro->facilitadores()->count())->toBe(0)
        ->and($infocentro->equipos()->count())->toBe(0)
        ->and($infocentro->energias()->count())->toBe(0)
        ->and($infocentro->actividades()->count())->toBe(0)
        ->and($infocentro->vinculacionesMincyt()->count())->toBe(0);
});

test('eliminar un infocentro borra su cuenta, no la deja huérfana', function () {
    // Este es el riesgo de verdad: `users.infocentro_id` es `SET NULL`, así que
    // un borrado hecho al revés deja a la persona entrando al sistema con una
    // cuenta que ya no lleva a ningún infocentro. Nadie lo notaría hasta que
    // alguien intente trabajar con ella.
    $admin = AdminFactory::new()->create();
    $infocentro = infocentroCompleto();
    $usuarioId = $infocentro->user->id;
    $username = $infocentro->user->username;

    expect(User::find($usuarioId))->not->toBeNull();

    $this->actingAs($admin)->delete("/admin/infocentros/{$infocentro->id}");

    expect(User::find($usuarioId))->toBeNull('La cuenta no puede quedar viva sin infocentro.')
        ->and(User::where('username', $username)->count())->toBe(0);
});

test('eliminar un infocentro no deja las asignaciones de rol colgando', function () {
    $admin = AdminFactory::new()->create();
    $infocentro = infocentroCompleto();
    $usuarioId = $infocentro->user->id;

    expect(DB::table('model_has_roles')->where('model_id', $usuarioId)->count())->toBe(1);

    $this->actingAs($admin)->delete("/admin/infocentros/{$infocentro->id}");

    // Spatie desengancha en su evento `deleting`. Si algún día dejara de hacerlo,
    // estas filas quedarían apuntando a un usuario que ya no existe.
    expect(DB::table('model_has_roles')->where('model_id', $usuarioId)->count())->toBe(0);
});

test('eliminar un infocentro borra sus documentos del disco', function () {
    Storage::fake(config('filesystems.default'));

    $admin = AdminFactory::new()->create();
    $infocentro = infocentroCompleto();

    $ruta = "diagnostico/{$infocentro->id}/plan.pdf";
    Storage::disk(config('filesystems.default'))->put($ruta, 'contenido');
    DocumentoAnexoFactory::new()->for($infocentro)->create(['ruta' => $ruta]);

    $this->actingAs($admin)->delete("/admin/infocentros/{$infocentro->id}");

    // La cascada solo ve la base de datos: los ficheros se van aparte.
    expect(Storage::disk(config('filesystems.default'))->exists($ruta))->toBeFalse()
        ->and(DB::table('documentos_anexos')->where('infocentro_id', $infocentro->id)->count())->toBe(0);
});

test('eliminar un infocentro sin cuenta ni ficha no falla', function () {
    $admin = AdminFactory::new()->create();
    $infocentro = Infocentro::factory()->create();

    $this->actingAs($admin)
        ->delete("/admin/infocentros/{$infocentro->id}")
        ->assertRedirect(route('admin.infocentros.index'))
        ->assertSessionHasNoErrors();

    expect(Infocentro::find($infocentro->id))->toBeNull();
});

test('eliminar un infocentro no toca el de al lado', function () {
    $admin = AdminFactory::new()->create();
    $primero = infocentroCompleto();
    $segundo = infocentroCompleto();

    $usuarioSegundo = $segundo->user->username;

    $this->actingAs($admin)->delete("/admin/infocentros/{$primero->id}");

    expect($segundo->refresh()->nombre)->not->toBeNull()
        ->and($segundo->user->username)->toBe($usuarioSegundo)
        ->and($segundo->facilitadores()->count())->toBe(1);
});

test('solo un administrador puede eliminar un infocentro', function () {
    $infocentro = infocentroCompleto();

    $this->actingAs($infocentro->user)
        ->delete("/admin/infocentros/{$infocentro->id}")
        ->assertForbidden();

    $this->actingAs(UserFactory::new()->create())
        ->delete("/admin/infocentros/{$infocentro->id}")
        ->assertForbidden();

    expect(Infocentro::find($infocentro->id))->not->toBeNull();
});

test('al borrar se vuelve al listado con sus filtros', function () {
    $admin = AdminFactory::new()->create();
    $infocentro = infocentroCompleto();

    $this->actingAs($admin)
        ->delete("/admin/infocentros/{$infocentro->id}?q=abc&ficha=hecha&page=3")
        ->assertRedirect(route('admin.infocentros.index', ['q' => 'abc', 'ficha' => 'hecha', 'page' => 3]));
});

test('el aviso nombra el código y la cuenta que se han borrado', function () {
    $admin = AdminFactory::new()->create();
    $infocentro = infocentroCompleto();

    $codigo = $infocentro->codigo;
    $usuario = $infocentro->user->username;

    $this->actingAs($admin)->delete("/admin/infocentros/{$infocentro->id}");

    $aviso = session('status');

    expect(str_contains((string) $aviso, $codigo))->toBeTrue()
        ->and(str_contains((string) $aviso, $usuario))->toBeTrue();
});

/**
 * Saca del HTML el payload que el modal recibe y lo devuelve como array.
 *
 * `@js()` serializa con comillas unicode escapadas, así que buscar una cadena
 * suelta en el HTML no dice nada. Se extrae el `JSON.parse` y se decodifica.
 *
 * @return array<int|string, array<string, mixed>>
 */
function payloadDelModal(string $html): array
{
    preg_match("#eliminarInfocentro\(JSON\.parse\('([^']*)'#", $html, $encontrado);

    return json_decode(str_replace(['\u0022', '\\\\'], ['"', '\\'], $encontrado[1] ?? '{}'), true) ?: [];
}

test('el modal lleva el detalle de la fila que se pulsó', function () {
    $admin = AdminFactory::new()->create();
    $conFicha = infocentroCompleto(['nombre' => 'Infocentro con ficha de prueba']);
    $sinFicha = Infocentro::factory()->create(['nombre' => 'Infocentro sin ficha de prueba']);

    $html = $this->actingAs($admin)->get('/admin/infocentros')->getContent();
    $payload = payloadDelModal($html);

    // Indexado por id, para que el componente sepa a qué fila se pulsó.
    expect($payload)->toHaveKey($conFicha->id)
        ->and($payload)->toHaveKey($sinFicha->id);

    expect($payload[$conFicha->id])
        ->toMatchArray([
            'nombre' => 'Infocentro con ficha de prueba',
            'codigo' => $conFicha->codigo,
            'usuario' => $conFicha->user->username,
            'ficha' => true,
        ]);

    expect($payload[$sinFicha->id])->toMatchArray(['ficha' => false]);

    // Y el botón de cada fila dice a cuál se está eliminando.
    expect($html)
        ->toContain("\$dispatch('abrir-infocentro', {$conFicha->id})")
        ->toContain("\$dispatch('abrir-infocentro', {$sinFicha->id})");
});

test('el modal avisa de la ficha que se va con el infocentro', function () {
    $admin = AdminFactory::new()->create();
    infocentroCompleto();

    $html = $this->actingAs($admin)->get('/admin/infocentros')->getContent();

    expect($html)->toContain('Además tiene una ficha de diagnóstico guardada')
        ->toContain('No se puede deshacer.')
        // La recarga desde el archivo maestro se quitó del modal: era ruido y
        // además daba una falsa sensación de red, porque no devuelve la ficha.
        ->not->toContain('InfocentrosArchivoSeeder');
});

test('el payload trae los contadores de las secciones', function () {
    Storage::fake(config('filesystems.default'));

    $admin = AdminFactory::new()->create();
    $infocentro = infocentroCompleto();

    $ruta = "diagnostico/{$infocentro->id}/plan.pdf";
    Storage::disk(config('filesystems.default'))->put($ruta, 'contenido');
    DocumentoAnexoFactory::new()->for($infocentro)->create(['ruta' => $ruta]);

    $payload = payloadDelModal($this->actingAs($admin)->get('/admin/infocentros')->getContent());

    // Los números que se pierden son la mitad del valor de la confirmación.
    expect($payload[$infocentro->id])->toMatchArray([
        'facilitadores' => 1,
        'equipos' => 1,
        'energias' => 1,
        'actividades' => 1,
        'vinculaciones' => 1,
        'documentos' => 1,
    ]);
});

test('el formulario del modal ata su action a los datos, no a una URL escrita', function () {
    $admin = AdminFactory::new()->create();
    infocentroCompleto();

    $html = $this->actingAs($admin)->get('/admin/infocentros')->getContent();

    // `action` lo calcula Alpine con el id abierto y los filtros de la URL. Si
    // estuviera escrito a mano habría que reescribirlo en cada fila, y con
    // quince filas es donde se cuelan los ids equivocados.
    expect($html)->toContain(':action="action"')
        ->and($html)->not->toContain('action="/admin/infocentros/1"');
});

test('el listado no hace una consulta por fila', function () {
    $admin = AdminFactory::new()->create();
    infocentroCompleto();
    Infocentro::factory()->count(14)->create();

    // `withCount` resuelve los seis contadores con seis agregadas para toda la
    // página. Si el los sacara con `$infocentro->x()->count()`, quince filas
    // serían noventa consultas en el listado de entrada.
    $queries = 0;

    DB::listen(function () use (&$queries): void {
        $queries++;
    });

    $this->actingAs($admin)->get('/admin/infocentros')->assertOk();

    expect($queries)->toBeLessThan(20);
});

test('al volver no se reenvían parámetros que no son del listado', function () {
    $admin = AdminFactory::new()->create();
    $infocentro = infocentroCompleto();

    // La redirección se construye con una lista corta de filtros, no con todo lo
    // que venga en la petición. Una URL manipulada no puede colar nada en ella.
    $this->actingAs($admin)
        ->delete("/admin/infocentros/{$infocentro->id}?q=abc&inyectado=https%3A%2F%2Fotro-sitio.example")
        ->assertRedirect(route('admin.infocentros.index', ['q' => 'abc']));
});

test('el evento que abre el modal y su nombre no se desincronizan', function () {
    $admin = AdminFactory::new()->create();
    infocentroCompleto();

    $html = $this->actingAs($admin)->get('/admin/infocentros')->getContent();
    $js = file_get_contents(resource_path('js/app.js'));

    // El nombre del modal solo se lee en el fuente: en el HTML ya está
    // compilado y solo queda dentro del `x-on:open-modal` que genera.
    $blade = file_get_contents(resource_path('views/admin/infocentros/index.blade.php'));

    // El botón dispara `abrir-infocentro` y el componente devuelve
    // `open-modal` con el nombre del modal. Si uno de los dos nombres se renombra
    // sin el otro, el modal deja de abrir sin que nada falle: el contenido se
    // carga en la página y simplemente nunca se ve. Es lo que pasó una vez.
    expect($html)->toContain("\$dispatch('abrir-infocentro'")
        ->and($blade)->toContain('<x-modal name="eliminar-infocentro"')
        ->and($js)->toContain("\$dispatch('open-modal', 'eliminar-infocentro')");
});

test('el panel del modal se pinta por encima de su propio fondo', function () {
    $blade = file_get_contents(resource_path('views/components/modal.blade.php'));

    // El hit-testing de CSS no se puede comprobar desde Pest, así que lo que se
    // fija es la causa: sin una posición, el panel queda `position: static` y se
    // pinta por debajo del fondo `fixed inset-0` de dentro del modal. Con
    // Tailwind v3 la clase suelta `transform` lo tapaba; con v4 no, y el modal
    // entero quedaba inpulsable sin ningún error visible.
    expect($blade)->toMatch('/x-show="show"\s+class="relative z-10 /');
});
