<?php

use App\Enums\RoleEnum;
use App\Models\Infocentro;
use App\Models\User;
use Database\Factories\AdminFactory;
use Database\Factories\InfocentroUserFactory;
use Illuminate\Support\Facades\Hash;

test('administrator can create an infocentro with its user', function () {
    $admin = AdminFactory::new()->create();

    $response = $this->actingAs($admin)->post('/admin/infocentros', [
        'nombre' => 'Infocentro Sur',
        'codigo' => 'INF-003',
        'direccion' => 'Calle Sur 100',
        'telefono' => '5551003',
        'activo' => 1,
        'user_name' => 'Usuario Sur',
        'user_username' => 'infocentro3',
        'user_email' => 'sur@infocentro.test',
        'user_password' => 'password',
        'user_password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('admin.infocentros.index'));

    $infocentro = Infocentro::where('codigo', 'INF-003')->sole();

    expect($infocentro->nombre)->toBe('Infocentro Sur')
        ->and($infocentro->activo)->toBeTrue();

    $user = $infocentro->user()->sole();

    expect($user->username)->toBe('infocentro3')
        ->and($user->email)->toBe('sur@infocentro.test')
        ->and($user->hasRole(RoleEnum::Infocentro))->toBeTrue()
        ->and(Hash::check('password', $user->password))->toBeTrue();
});

test('created infocentro user belongs to its infocentro and has no administrator role', function () {
    $admin = AdminFactory::new()->create();

    $this->actingAs($admin)->post('/admin/infocentros', [
        'nombre' => 'Infocentro Oeste',
        'codigo' => 'INF-004',
        'user_name' => 'Usuario Oeste',
        'user_username' => 'infocentro4',
        'user_email' => 'oeste@infocentro.test',
        'user_password' => 'password',
        'user_password_confirmation' => 'password',
    ])->assertSessionHasNoErrors();

    $infocentro = Infocentro::where('codigo', 'INF-004')->sole();

    expect($infocentro->user->infocentro_id)->toBe($infocentro->id)
        ->and($infocentro->user->hasRole(RoleEnum::Administrador))->toBeFalse();
});

test('administrator can edit an infocentro and its user', function () {
    $admin = AdminFactory::new()->create();
    $infocentro = Infocentro::factory()->inactivo()->create();
    $user = $infocentro->user()->create([
        'name' => 'Nombre Anterior',
        'username' => 'anterior',
        'email' => 'anterior@infocentro.test',
        'password' => 'password',
    ]);

    $response = $this->actingAs($admin)->put("/admin/infocentros/{$infocentro->id}", [
        'nombre' => 'Infocentro Renombrado',
        'codigo' => 'INF-999',
        'direccion' => 'Nueva Dirección 1',
        'telefono' => '5559999',
        'activo' => 1,
        'user_name' => 'Nombre Nuevo',
        'user_username' => 'nuevo',
        'user_email' => 'nuevo@infocentro.test',
        'user_password' => '',
        'user_password_confirmation' => '',
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('admin.infocentros.index'));

    $infocentro->refresh();
    $user->refresh();

    expect($infocentro->nombre)->toBe('Infocentro Renombrado')
        ->and($infocentro->codigo)->toBe('INF-999')
        ->and($infocentro->activo)->toBeTrue()
        ->and($user->username)->toBe('nuevo')
        ->and($user->email)->toBe('nuevo@infocentro.test')
        ->and(Hash::check('password', $user->password))->toBeTrue();
});

test('administrator can set a new password when editing', function () {
    $admin = AdminFactory::new()->create();
    $infocentro = Infocentro::factory()->create();
    $user = $infocentro->user()->create([
        'name' => 'Usuario',
        'username' => 'usuario',
        'email' => 'usuario@infocentro.test',
        'password' => 'password',
    ]);

    $this->actingAs($admin)->put("/admin/infocentros/{$infocentro->id}", [
        'nombre' => $infocentro->nombre,
        'codigo' => $infocentro->codigo,
        'user_name' => $user->name,
        'user_username' => $user->username,
        'user_email' => $user->email,
        'user_password' => 'nueva-clave',
        'user_password_confirmation' => 'nueva-clave',
    ])->assertSessionHasNoErrors();

    expect(Hash::check('nueva-clave', $user->refresh()->password))->toBeTrue();
});

test('infocentro codes must be unique', function () {
    $admin = AdminFactory::new()->create();
    Infocentro::factory()->create(['codigo' => 'INF-005']);

    $this->actingAs($admin)->post('/admin/infocentros', [
        'nombre' => 'Duplicado',
        'codigo' => 'INF-005',
        'user_name' => 'Usuario',
        'user_username' => 'duplicado',
        'user_email' => 'duplicado@infocentro.test',
        'user_password' => 'password',
        'user_password_confirmation' => 'password',
    ])->assertSessionHasErrors('codigo');
});

test('usernames must be unique', function () {
    $admin = AdminFactory::new()->create();
    AdminFactory::new()->create(['username' => 'ocupado']);

    $this->actingAs($admin)->post('/admin/infocentros', [
        'nombre' => 'Infocentro',
        'codigo' => 'INF-006',
        'user_name' => 'Usuario',
        'user_username' => 'ocupado',
        'user_email' => 'nuevo@infocentro.test',
        'user_password' => 'password',
        'user_password_confirmation' => 'password',
    ])->assertSessionHasErrors('user_username');
});

test('infocentros can not be created by guests', function () {
    $this->post('/admin/infocentros', [])->assertRedirect('/login');
});

test('infocentros can not be created by infocentro users', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->post('/admin/infocentros', [])->assertForbidden();
});

test('administrator can deactivate an infocentro', function () {
    $admin = AdminFactory::new()->create();
    $infocentro = Infocentro::factory()->create();
    $user = $infocentro->user()->create([
        'name' => 'Usuario',
        'username' => 'usuario',
        'email' => 'usuario@infocentro.test',
        'password' => 'password',
    ]);

    $this->actingAs($admin)->put("/admin/infocentros/{$infocentro->id}", [
        'nombre' => $infocentro->nombre,
        'codigo' => $infocentro->codigo,
        'user_name' => $user->name,
        'user_username' => $user->username,
        'user_email' => $user->email,
    ])->assertSessionHasNoErrors();

    expect($infocentro->refresh()->activo)->toBeFalse();
});

test('a password is required when the infocentro has no user yet', function () {
    $admin = AdminFactory::new()->create();
    $infocentro = Infocentro::factory()->create();

    $this->actingAs($admin)->put("/admin/infocentros/{$infocentro->id}", [
        'nombre' => $infocentro->nombre,
        'codigo' => $infocentro->codigo,
        'user_name' => 'Usuario',
        'user_username' => 'sinusuario',
        'user_email' => 'sinusuario@infocentro.test',
    ])->assertSessionHasErrors('user_password');

    expect($infocentro->user()->exists())->toBeFalse();
});

test('a deleted infocentro keeps its user but clears the foreign key', function () {
    $infocentro = Infocentro::factory()->create();
    $user = $infocentro->user()->create([
        'name' => 'Usuario',
        'username' => 'huérfano',
        'email' => 'huerfano@infocentro.test',
        'password' => 'password',
    ]);

    $infocentro->delete();

    expect(User::find($user->id))->not->toBeNull()
        ->and($user->refresh()->infocentro_id)->toBeNull();
});
