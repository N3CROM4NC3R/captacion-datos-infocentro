<?php

use App\Enums\RoleEnum;
use App\Models\Infocentro;
use App\Models\User;
use Database\Factories\InfocentroUserFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

test('el técnico entra a todo lo que entra el administrador general', function () {
    $tecnico = administradorTecnicoDePrueba();

    // Las tres puertas que deciden si alguien entra al panel. Arreglar el rol
    // nuevo en dos y olvidar la tercera deja al técnico entrando y saliendo
    // despedido hacia el formulario de ficha, que no puede abrir porque no
    // tiene infocentro.
    foreach (['/admin/dashboard', '/admin/infocentros', '/admin/fichas'] as $ruta) {
        $this->actingAs($tecnico)->get($ruta)->assertOk();
    }

    $this->actingAs($tecnico)->get('/dashboard')->assertRedirect(route('admin.dashboard'));
});

test('el técnico entra por el login al panel, no a la ficha', function () {
    $tecnico = administradorTecnicoDePrueba();

    $this->post('/login', [
        'username' => $tecnico->username,
        'password' => 'password',
    ])->assertRedirect(route('admin.dashboard', absolute: false));
});

test('un administrador general no ve el enlace ni llega a la ruta', function () {
    $general = administradorGeneralDePrueba();

    // Son dos cosas distintas: que el enlace no se pinte y que la ruta no
    // responda. Un formulario oculto por una URL escrita a mano sigue siendo un
    // formulario al que se puede llegar.
    $this->actingAs($general)
        ->get('/admin/dashboard')
        ->assertOk()
        ->assertDontSee(route('admin.administradores.index'), false);

    $this->actingAs($general)
        ->get('/admin/administradores')
        ->assertForbidden();
});

test('un facilitador tampoco llega al panel', function () {
    $facilitador = InfocentroUserFactory::new()->create();

    $this->actingAs($facilitador)->get('/admin/administradores')->assertForbidden();

    $this->actingAs($facilitador)->get('/admin/dashboard')->assertForbidden();
});

test('el listado muestra solo los administradores generales', function () {
    $tecnico = administradorTecnicoDePrueba();
    $general = administradorGeneralDePrueba(['name' => 'General Listable']);
    $otro = administradorGeneralDePrueba(['name' => 'Otro Listable']);
    $facilitador = InfocentroUserFactory::new()->create();

    $html = $this->actingAs($tecnico)->get('/admin/administradores')->assertOk()->getContent();

    // Solo el cuerpo de la tabla: en la página entera el técnico sí aparece,
    // porque la navegación muestra quién ha entrado.
    preg_match('/<tbody.*?<\/tbody>/s', $html, $cuerpo);

    expect($cuerpo[0])->toContain($general->username)
        ->toContain($otro->username)
        // Ni el propio técnico ni los facilitadores: no son gestionables aquí.
        ->not->toContain($tecnico->username)
        ->not->toContain($facilitador->username);
});

test('dar de alta deja rol general y nunca el técnico', function () {
    $tecnico = administradorTecnicoDePrueba();

    $this->actingAs($tecnico)->post('/admin/administradores', [
        'name' => 'Nueva Cuenta',
        'username' => 'nuevacuenta',
        'email' => 'nuevacuenta@infocentro.test',
        'password' => 'contrasena-larga-1',
        'password_confirmation' => 'contrasena-larga-1',
    ])->assertRedirect(route('admin.administradores.index'));

    $creado = User::where('username', 'nuevacuenta')->first();

    expect($creado)->not->toBeNull()
        ->and($creado->esAdministrador())->toBeTrue()
        ->and($creado->hasRole(RoleEnum::Administrador))->toBeTrue();

    // El freno de escalada: si el alta pudiera dejar el rol técnico, quien
    // tuviera el rol podría multiplicarlo y el módulo perdería su sentido.
    expect($creado->hasRole(RoleEnum::AdministradorTecnico))->toBeFalse();
});

test('el alta ignora un rol que venga en el payload', function () {
    $tecnico = administradorTecnicoDePrueba();

    $this->actingAs($tecnico)->post('/admin/administradores', [
        'name' => 'Con Rol Colado',
        'username' => 'colado',
        'email' => 'colado@infocentro.test',
        'password' => 'contrasena-larga-1',
        'password_confirmation' => 'contrasena-larga-1',
        'role' => RoleEnum::AdministradorTecnico->value,
        'roles' => [RoleEnum::AdministradorTecnico->value],
    ]);

    expect(User::where('username', 'colado')->first()->hasRole(RoleEnum::AdministradorTecnico))->toBeFalse();
});

test('el alta valida y no duplica usuarios ni correos', function () {
    $tecnico = administradorTecnicoDePrueba();
    $existente = administradorGeneralDePrueba(['username' => 'repetido']);

    $this->actingAs($tecnico)
        ->post('/admin/administradores', [
            'name' => '',
            'username' => $existente->username,
            'email' => $existente->email,
            'password' => 'corta',
            'password_confirmation' => 'otra',
        ])
        ->assertSessionHasErrors(['name', 'username', 'email', 'password']);

    // Al corregir, el propio usuario y correo no cuentan como duplicado.
    $this->actingAs($tecnico)
        ->patch("/admin/administradores/{$existente->id}", [
            'name' => 'Renombrado',
            'username' => $existente->username,
            'email' => $existente->email,
        ])
        ->assertSessionHasNoErrors();
});

test('corregir no cambia la contraseña si se deja vacía', function () {
    $tecnico = administradorTecnicoDePrueba();
    $general = administradorGeneralDePrueba();
    $antes = $general->password;

    $this->actingAs($tecnico)->patch("/admin/administradores/{$general->id}", [
        'name' => 'Nombre Corregido',
        'username' => $general->username,
        'email' => $general->email,
    ])->assertRedirect(route('admin.administradores.index'));

    $general->refresh();

    expect($general->name)->toBe('Nombre Corregido')
        ->and($general->password)->toBe($antes);
});

test('corregir sí cambia la contraseña si se manda', function () {
    $tecnico = administradorTecnicoDePrueba();
    $general = administradorGeneralDePrueba();
    $antes = $general->password;

    $this->actingAs($tecnico)->patch("/admin/administradores/{$general->id}", [
        'name' => $general->name,
        'username' => $general->username,
        'email' => $general->email,
        'password' => 'contrasena-larga-9',
        'password_confirmation' => 'contrasena-larga-9',
    ]);

    expect($general->refresh()->password)->not->toBe($antes)
        ->and(Hash::check('contrasena-larga-9', $general->password))->toBeTrue();
});

test('eliminar se lleva el usuario y no deja roles colgando', function () {
    $tecnico = administradorTecnicoDePrueba();
    $general = administradorGeneralDePrueba();
    $id = $general->id;

    $this->actingAs($tecnico)
        ->delete("/admin/administradores/{$id}")
        ->assertRedirect(route('admin.administradores.index'));

    expect(User::find($id))->toBeNull()
        ->and(DB::table('model_has_roles')->where('model_id', $id)->count())->toBe(0);
});

test('con un solo rol técnico, borrarse ya da 404 por el enlace de ruta', function () {
    // Un técnico puro no llega ni a plantearse el borrado: el enlace de ruta
    // solo resuelve administradores generales, así que esa URL no existe.
    $tecnico = administradorTecnicoDePrueba();

    $this->actingAs($tecnico)
        ->delete("/admin/administradores/{$tecnico->id}")
        ->assertNotFound();

    expect(User::find($tecnico->id))->not->toBeNull();
});

test('nadie se borra a sí mismo aunque tenga los dos roles', function () {
    // Aquí sí se resuelve, porque la cuenta es también administrador general, y
    // sin el cartel del controlador el técnico se quedaría fuera del panel sin
    // nadie que se lo devolviera. El estado es raro —el seeder no lo produce—
    // pero es el único en el que el borrado sería posible.
    $tecnico = administradorTecnicoDePrueba();
    $tecnico->assignRole(Role::findOrCreate(RoleEnum::Administrador));

    $this->actingAs($tecnico)
        ->delete("/admin/administradores/{$tecnico->id}")
        ->assertForbidden();

    expect(User::find($tecnico->id))->not->toBeNull();
});

test('un técnico o un facilitador da 404 por URL, noForbidden', function () {
    $tecnico = administradorTecnicoDePrueba();
    $otroTecnico = administradorTecnicoDePrueba();
    $facilitador = InfocentroUserFactory::new()->create();

    // Acotar el enlace de ruta hace que esa URL no llegue a existir: responder
    // Forbidden después de haber cargado el registro ya dice demasiado.
    foreach ([$otroTecnico->id, $facilitador->id] as $id) {
        $this->actingAs($tecnico)->get("/admin/administradores/{$id}/editar")->assertNotFound();
        $this->actingAs($tecnico)->patch("/admin/administradores/{$id}", [])->assertNotFound();
        $this->actingAs($tecnico)->delete("/admin/administradores/{$id}")->assertNotFound();
    }
});

test('un administrador general borrado ya no puede entrar', function () {
    $tecnico = administradorTecnicoDePrueba();
    $general = administradorGeneralDePrueba();

    $this->actingAs($tecnico)->delete("/admin/administradores/{$general->id}");

    $this->post('/logout');

    $this->post('/login', [
        'username' => $general->username,
        'password' => 'password',
    ])->assertSessionHasErrors();
});

test('una cuenta con rol general y centro asignado tampoco se gestiona aquí', function () {
    $tecnico = administradorTecnicoDePrueba();
    $híbrido = administradorGeneralDePrueba();

    // Rol de administrador con un infocentro asignado sería una tercera cosa:
    // ese centro tiene su propia ficha y su propia cuenta, y borrarla desde
    // aquí se llevaría también el centro. El rol solo no basta para que la
    // cuenta entre en este módulo, y por eso el enlace de ruta mira también que
    // no tenga centro.
    Infocentro::factory()->create([
        'nombre' => 'Centro Con Admin',
        'codigo' => 'HIBRIDO-1',
    ]);
    // `infocentro_id` no es fillable en `User`: un `update()` con él dentro se
    // lo come en silencio y la cuenta se queda sin centro, que es justo lo que
    // el test viene a comprobar.
    $híbrido->forceFill(['infocentro_id' => Infocentro::where('codigo', 'HIBRIDO-1')->value('id')])->save();

    $this->actingAs($tecnico)
        ->get("/admin/administradores/{$híbrido->id}/editar")
        ->assertNotFound();

    $this->actingAs($tecnico)
        ->delete("/admin/administradores/{$híbrido->id}")
        ->assertNotFound();

    // Y tampoco sale en el listado, aunque sí sea administrador general.
    $this->actingAs($tecnico)
        ->get('/admin/administradores')
        ->assertDontSee($híbrido->username);
});
