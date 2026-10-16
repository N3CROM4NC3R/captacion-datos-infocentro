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
