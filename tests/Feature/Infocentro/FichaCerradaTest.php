<?php

use App\Enums\TipoEquipoEnum;
use App\Models\DocumentoAnexo;
use App\Models\User;
use Database\Factories\AdminFactory;
use Database\Factories\DocumentoAnexoFactory;
use Database\Factories\InfocentroUserFactory;
use Illuminate\Support\Facades\Storage;

/**
 * Un facilitador cuya ficha ya está guardada, con el telefono conocido.
 */
function facilitadorConFichaGuardada(): User
{
    $user = InfocentroUserFactory::new()->create();

    test()->actingAs($user)->put('/infocentro/diagnostico', [
        'nombre' => $user->infocentro->nombre,
        'estado_id' => $user->infocentro->estado_id,
        'activo' => '1',
        'telefono' => '04121234567',
    ])->assertSessionHasNoErrors();

    return $user;
}

test('una ficha guardada se ve pero no se toca', function () {
    $user = facilitadorConFichaGuardada();

    $html = $this->actingAs($user)->get('/infocentro/diagnostico')->assertOk()->getContent();

    // Lo que se ve: los datos guardados, y el aviso de que ya está cerrada.
    expect($html)->toContain('04121234567')
        ->toContain('Tu ficha está guardada')
        // Y lo que no se ve: el formulario y su botón. Lo que marca el formulario
        // de guardado es el `@method('PUT')`; buscar la URL entera no sirve
        // porque el menú enlaza a la propia ficha.
        ->not->toMatch('/name="_method"\s+value="PUT"/')
        ->not->toContain('Guardar');

    // Los campos van dentro de un fieldset deshabilitado. Sin él se verían
    // normales y parecerían editables.
    expect($html)->toMatch('/<fieldset[^>]*\sdisabled/');
});

test('un segundo guardado no cambia ni los campos ni las filas', function () {
    $user = facilitadorConFichaGuardada();
    $infocentro = $user->infocentro->refresh();

    $telefono = $infocentro->telefono;
    $guardado = $infocentro->diagnostico_guardado_at;

    $this->actingAs($user)
        ->put('/infocentro/diagnostico', [
            'nombre' => 'Nombre Cambiado',
            'estado_id' => $infocentro->estado_id,
            'activo' => '0',
            'telefono' => '00000000',
            // Y una fila nueva, que tampoco debe aparecer.
            'equipos' => [['tipo' => TipoEquipoEnum::Computadoras->value, 'cantidad' => '9']],
        ])
        ->assertRedirect(route('infocentro.diagnostico.edit'))
        ->assertSessionHas('aviso');

    $infocentro->refresh();

    expect($infocentro->nombre)->not->toBe('Nombre Cambiado')
        ->and($infocentro->telefono)->toBe($telefono)
        ->and($infocentro->activo)->toBeTrue()
        ->and($infocentro->diagnostico_guardado_at->timestamp)->toBe($guardado->timestamp)
        ->and($infocentro->equipos()->count())->toBe(0);
});

test('un segundo guardado no cambia los documentos ya subidos', function () {
    Storage::fake(config('filesystems.default'));

    $user = InfocentroUserFactory::new()->create();
    $infocentro = $user->infocentro;

    $ruta = "diagnostico/{$infocentro->id}/plan.pdf";
    Storage::disk(config('filesystems.default'))->put($ruta, 'contenido');
    DocumentoAnexoFactory::new()->for($infocentro)->create(['ruta' => $ruta]);

    test()->actingAs($user)->put('/infocentro/diagnostico', [
        'nombre' => $infocentro->nombre,
        'estado_id' => $infocentro->estado_id,
        'activo' => '1',
    ])->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->put('/infocentro/diagnostico', [
            'nombre' => $infocentro->nombre,
            'estado_id' => $infocentro->estado_id,
            'activo' => '1',
            'documentos_eliminar' => ['plan' => '1'],
        ])
        ->assertSessionHas('aviso');

    expect(DocumentoAnexo::count())->toBe(1)
        ->and(Storage::disk(config('filesystems.default'))->exists($ruta))->toBeTrue();
});

test('el rechazo pasa antes de validar, así que no filtra datos del formulario', function () {
    $user = facilitadorConFichaGuardada();

    // Si el corte estuviera después de validar, un guardado con campos
    // inválidos respondería con errores de validación en vez de con el aviso,
    // que es lo que distingue un rechazo de un formulario roto.
    $this->actingAs($user)
        ->put('/infocentro/diagnostico', [])
        ->assertRedirect(route('infocentro.diagnostico.edit'))
        ->assertSessionHas('aviso')
        ->assertSessionHasNoErrors();
});

test('una ficha sin guardar se ve con su formulario y se guarda', function () {
    $user = InfocentroUserFactory::new()->create();

    // Antes de guardar, la ficha es un formulario normal.
    $this->actingAs($user)->get('/infocentro/diagnostico')
        ->assertOk()
        ->assertSee('Guardar')
        ->assertSee('action="'.route('infocentro.diagnostico.update').'"', false)
        ->assertSee('name="_method"', false);

    $this->actingAs($user)
        ->put('/infocentro/diagnostico', [
            'nombre' => 'Primera vez',
            'estado_id' => $user->infocentro->estado_id,
            'activo' => '1',
        ])
        ->assertSessionHasNoErrors();

    expect($user->infocentro->refresh()->nombre)->toBe('Primera vez');

    // Y en cuanto se guarda, esa misma página pasa a solo lectura.
    $this->actingAs($user)->get('/infocentro/diagnostico')
        ->assertOk()
        ->assertSee('Tu ficha está guardada')
        ->assertDontSee('Guardar');
});

test('la vía de corrección sigue viva: vaciar y volver a guardar', function () {
    // Lo que hace un facilitador que se equivocó es pedir que le vacíen la
    // ficha. Si esa vuelta dejó de funcionar, el cierre sería una puerta sin
    // salida.
    $user = facilitadorConFichaGuardada();
    $admin = AdminFactory::new()->create();

    $this->actingAs($admin)->delete("/admin/fichas/{$user->infocentro->id}")->assertRedirect();

    expect($user->infocentro->refresh()->diagnostico_guardado_at)->toBeNull();

    $this->actingAs($user)->get('/infocentro/diagnostico')->assertSee('Guardar');

    $this->actingAs($user)->put('/infocentro/diagnostico', [
        'nombre' => 'Corregida',
        'estado_id' => $user->infocentro->estado_id,
        'activo' => '1',
    ])->assertSessionHasNoErrors();

    $infocentro = $user->infocentro->refresh();

    expect($infocentro->nombre)->toBe('Corregida')
        ->and($infocentro->diagnostico_guardado_at)->not->toBeNull();
});

test('el aviso del rechazo se ve al volver a la ficha', function () {
    $user = facilitadorConFichaGuardada();

    $this->actingAs($user)
        ->put('/infocentro/diagnostico', [
            'nombre' => $user->infocentro->nombre,
            'estado_id' => $user->infocentro->estado_id,
            'activo' => '1',
        ]);

    $this->actingAs($user)
        ->get('/infocentro/diagnostico')
        ->assertOk()
        ->assertSee('ya está guardada');
});
