<?php

use App\Enums\PermisoAdminEnum;
use App\Models\Infocentro;
use App\Models\User;
use Database\Factories\AdminFactory;
use Database\Factories\InfocentroUserFactory;
use Spatie\Permission\PermissionRegistrar;

test('un administrador con solo ver llega al listado y a las fichas', function () {
    $admin = administradorQueSoloVe();

    $this->actingAs($admin)->get('/admin/infocentros')->assertOk();
    $this->actingAs($admin)->get('/admin/fichas')->assertOk();
    $this->actingAs($admin)->get('/admin/dashboard')->assertOk();

    expect($admin->fresh()->hasAnyPermission(PermisoAdminEnum::Ver->value))->toBeTrue();
});

test('sin permiso no puede ni ver el listado de infocentros', function () {
    $admin = AdminFactory::new()->conPermisos([])->create();

    $this->actingAs($admin)->get('/admin/infocentros')->assertForbidden();
    $this->actingAs($admin)->get('/admin/fichas')->assertForbidden();
});

test('sin permiso no puede crear, ni el formulario ni el alta', function () {
    $admin = administradorQueSoloVe();

    // No solo el POST: el formulario también. Si se dejara abierto, el
    // administrador vería un botón que al pulsar le suelta un 403.
    $this->actingAs($admin)->get('/admin/infocentros/create')->assertForbidden();
    $this->actingAs($admin)->post('/admin/infocentros', [])->assertForbidden();

    expect(Infocentro::count())->toBe(0);
});

test('sin permiso no puede editar, ni el formulario ni el guardado', function () {
    $admin = administradorQueSoloVe();
    $infocentro = Infocentro::factory()->create();

    $this->actingAs($admin)
        ->get("/admin/infocentros/{$infocentro->id}/edit")
        ->assertForbidden();

    $this->actingAs($admin)
        ->patch("/admin/infocentros/{$infocentro->id}", ['nombre' => 'Cambiado'])
        ->assertForbidden();

    expect($infocentro->refresh()->nombre)->not->toBe('Cambiado');
});

test('sin permiso no puede eliminar un infocentro', function () {
    $admin = administradorQueSoloVe();
    $infocentro = Infocentro::factory()->create();

    $this->actingAs($admin)
        ->delete("/admin/infocentros/{$infocentro->id}")
        ->assertForbidden();

    expect(Infocentro::find($infocentro->id))->not->toBeNull();
});

test('eliminar cubre también vaciar una ficha y su confirmación', function () {
    $admin = administradorQueSoloVe();
    $infocentro = Infocentro::factory()->create();

    // Vaciar una ficha se lleva el mismo trabajo de campo que borrar el
    // infocentro, así que comparte permiso y no tiene uno aparte.
    $this->actingAs($admin)
        ->get("/admin/fichas/{$infocentro->id}/eliminar")
        ->assertForbidden();

    $this->actingAs($admin)
        ->delete("/admin/fichas/{$infocentro->id}")
        ->assertForbidden();
});

test('con el permiso de eliminar también se puede vaciar una ficha', function () {
    $admin = AdminFactory::new()->conPermisos([PermisoAdminEnum::Eliminar->value])->create();
    $infocentro = infocentroConFichaDePrueba();

    $this->actingAs($admin)->get("/admin/fichas/{$infocentro->id}/eliminar")->assertOk();
    $this->actingAs($admin)->delete("/admin/fichas/{$infocentro->id}")->assertRedirect();
});

/**
 * Un infocentro con la ficha guardada, por el PUT real.
 *
 * Sin ficha esas rutas dan 404 por el motivo equivocado —no hay ficha que
 * vaciar— y la prueba no estaría midiendo el permiso sino el 404.
 */
function infocentroConFichaDePrueba(): Infocentro
{
    $infocentro = Infocentro::factory()->create();
    $usuario = InfocentroUserFactory::new()->forInfocentro($infocentro)->create();

    test()->actingAs($usuario)->put('/infocentro/diagnostico', [
        'nombre' => $infocentro->nombre,
        'estado_id' => $infocentro->estado_id,
        'activo' => '1',
    ])->assertSessionHasNoErrors();

    return $infocentro->refresh();
}

test('los botones que no puede usar no se pintan', function () {
    $admin = administradorQueSoloVe();
    $infocentro = infocentroConFichaDePrueba();

    $html = $this->actingAs($admin)->get('/admin/infocentros')->assertOk()->getContent();

    expect($html)->not->toContain(route('admin.infocentros.create'))
        ->not->toContain(route('admin.infocentros.edit', $infocentro))
        ->not->toContain(route('admin.fichas.eliminar', $infocentro));
});

test('con los cuatro permisos los botones vuelven a pintarse', function () {
    $admin = administradorGeneralDePrueba();
    $infocentro = infocentroConFichaDePrueba();

    $html = $this->actingAs($admin)->get('/admin/infocentros')->assertOk()->getContent();

    expect($html)->toContain(route('admin.infocentros.create'))
        ->toContain(route('admin.infocentros.edit', $infocentro))
        ->toContain(route('admin.fichas.eliminar', $infocentro));
});

test('sin ningún permiso el panel explica en vez de soltar un 403', function () {
    $admin = AdminFactory::new()->conPermisos([])->create();

    $this->actingAs($admin)
        ->get('/admin/dashboard')
        ->assertOk()
        ->assertSee('no tiene permisos asignados');
});

test('un facilitador no gana permisos nuevos', function () {
    $facilitador = InfocentroUserFactory::new()->create();

    expect($facilitador->fresh()->permissions)->toHaveCount(0);

    $this->actingAs($facilitador)->get('/admin/infocentros')->assertForbidden();
    $this->actingAs($facilitador)->get('/admin/dashboard')->assertForbidden();
});

test('el técnico conserva los cuatro y no es editable desde la interfaz', function () {
    $tecnico = administradorTecnicoDePrueba();

    expect($tecnico->fresh()->permissions)->toHaveCount(count(PermisoAdminEnum::cases()));

    // No sale en el listado, así que su casilla de permisos no existe y no hay
    // forma de quitárselos desde la aplicación.
    $html = $this->actingAs($tecnico)->get('/admin/administradores')->assertOk()->getContent();

    preg_match('/<tbody.*?<\/tbody>/s', $html, $cuerpo);

    expect($cuerpo[0] ?? '')->not->toContain($tecnico->username);
});

test('un administrador nuevo nace solo con ver', function () {
    $tecnico = administradorTecnicoDePrueba();

    $this->actingAs($tecnico)->post('/admin/administradores', [
        'name' => 'Cuenta Reciente',
        'username' => 'reciente',
        'email' => 'reciente@infocentro.test',
        'password' => 'contrasena-larga-1',
        'password_confirmation' => 'contrasena-larga-1',
        'permisos' => [PermisoAdminEnum::Ver->value],
    ])->assertRedirect(route('admin.administradores.index'));

    $creado = User::where('username', 'reciente')->first();

    expect($creado->permissions->pluck('name')->all())->toBe([PermisoAdminEnum::Ver->value]);
});

test('dar de alta sin marcar nada deja la cuenta sin permisos', function () {
    $tecnico = administradorTecnicoDePrueba();

    $this->actingAs($tecnico)->post('/admin/administradores', [
        'name' => 'Sin Permisos',
        'username' => 'sinpermisos',
        'email' => 'sinpermisos@infocentro.test',
        'password' => 'contrasena-larga-1',
        'password_confirmation' => 'contrasena-larga-1',
    ]);

    // Sin la casilla de «ver» marcada la cuenta no puede ni mirar el catálogo.
    // Puede ser lo que se quiera; lo que no puede es quedarse con los cuatro
    // por forgot.
    expect(User::where('username', 'sinpermisos')->first()->permissions)->toHaveCount(0);
});

test('el técnico amplía permisos y surte efecto de inmediato', function () {
    $tecnico = administradorTecnicoDePrueba();
    $admin = administradorQueSoloVe();
    $infocentro = Infocentro::factory()->create();

    $this->actingAs($admin)
        ->get("/admin/infocentros/{$infocentro->id}/edit")
        ->assertForbidden();

    $this->actingAs($tecnico)->patch("/admin/administradores/{$admin->id}", [
        'name' => $admin->name,
        'username' => $admin->username,
        'email' => $admin->email,
        'permisos' => [
            PermisoAdminEnum::Ver->value,
            PermisoAdminEnum::Editar->value,
        ],
    ])->assertRedirect(route('admin.administradores.index'));

    // Sin limpiar la caché de Spatie el cambio no se vería hasta que se vaciara
    // por otra vía, que es el mismo apaño que ya hace el proyecto con los roles.
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs(User::find($admin->id))
        ->get("/admin/infocentros/{$infocentro->id}/edit")
        ->assertOk();

    $this->actingAs($admin->fresh())
        ->get('/admin/infocentros/create')
        ->assertForbidden();
});

test('quitar un permiso le quita también el botón', function () {
    $tecnico = administradorTecnicoDePrueba();
    $admin = AdminFactory::new()->conPermisos([
        PermisoAdminEnum::Ver->value,
        PermisoAdminEnum::Crear->value,
    ])->create();

    expect($this->actingAs($admin)->get('/admin/infocentros')->getContent())
        ->toContain(route('admin.infocentros.create'));

    $this->actingAs($tecnico)->patch("/admin/administradores/{$admin->id}", [
        'name' => $admin->name,
        'username' => $admin->username,
        'email' => $admin->email,
        'permisos' => [PermisoAdminEnum::Ver->value],
    ]);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    expect($this->actingAs($admin->fresh())->get('/admin/infocentros')->getContent())
        ->not->toContain(route('admin.infocentros.create'));
});

test('los permisos que no existen en el enum se ignoran', function () {
    $tecnico = administradorTecnicoDePrueba();

    $this->actingAs($tecnico)->post('/admin/administradores', [
        'name' => 'Colados',
        'username' => 'colados',
        'email' => 'colados@infocentro.test',
        'password' => 'contrasena-larga-1',
        'password_confirmation' => 'contrasena-larga-1',
        'permisos' => [PermisoAdminEnum::Ver->value, 'infocentros.todo', 'inventado'],
    ]);

    $creado = User::where('username', 'colados')->first();

    expect($creado->permissions->pluck('name')->all())->toBe([PermisoAdminEnum::Ver->value]);
});

test('el formulario de permisos muestra los cuatro con sus etiquetas', function () {
    $tecnico = administradorTecnicoDePrueba();
    $admin = administradorQueSoloVe();

    $html = $this->actingAs($tecnico)
        ->get("/admin/administradores/{$admin->id}/editar")
        ->assertOk()
        ->getContent();

    foreach (PermisoAdminEnum::cases() as $permiso) {
        expect($html)->toContain($permiso->label())
            ->toContain('value="'.$permiso->value.'"');
    }

    file_put_contents('/tmp/opencode/permisos.html', $html);

    // Y solo «ver» viene marcada en una cuenta que solo puede ver.
    expect(preg_match('/value="'.preg_quote(PermisoAdminEnum::Ver->value, '/').'"[^>]*checked/', $html))->toBe(1);
});

test('cada ruta pide solo su permiso y no el de las demás', function () {
    // `Route::resource()->middleware([...])` no reparte por acción: se lo pone a
    // todas. Con el array por acción, editar exigía también «crear» y nadie que
    // no pudiera dar de alta un infocentro podía corregir uno existente.
    $admin = administradorGeneralDePrueba();

    $editables = AdminFactory::new()->conPermisos([PermisoAdminEnum::Editar->value])->create();
    $creables = AdminFactory::new()->conPermisos([PermisoAdminEnum::Crear->value])->create();
    $infocentro = Infocentro::factory()->create();

    // Editar sin poder crear.
    $this->actingAs($editables)->get("/admin/infocentros/{$infocentro->id}/edit")->assertOk();

    // Crear sin poder editar.
    $this->actingAs($creables)->get('/admin/infocentros/create')->assertOk();
    $this->actingAs($creables)->get("/admin/infocentros/{$infocentro->id}/edit")->assertForbidden();

    expect($admin->fresh()->permissions)->toHaveCount(4);
});

test('el formulario de alta viene con solo ver marcado', function () {
    $tecnico = administradorTecnicoDePrueba();

    $html = $this->actingAs($tecnico)
        ->get('/admin/administradores/crear')
        ->assertOk()
        ->getContent();

    // El default «solo ver» vive en el checkbox, no en el controlador: si aquí
    // no viene marcado, la cuenta nace viendo todo.
    foreach (PermisoAdminEnum::cases() as $permiso) {
        $marcado = preg_match(
            '/value="'.preg_quote($permiso->value, '/').'"[^>]*checked/',
            $html,
        ) === 1;

        expect($marcado)->toBe($permiso->porDefecto(), $permiso->label());
    }
});
