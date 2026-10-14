<?php

use App\Enums\RoleEnum;
use App\Models\Infocentro;
use App\Models\User;
use App\Support\LectorXlsx;
use Database\Seeders\InfocentrosArchivoSeeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

if (! class_exists(LectorXlsxFalso::class)) {
    /**
     * Lector que devuelve filas preparadas y recuerda la ruta que le pidieron.
     *
     * Evita que los tests intercambien el maestro real por el fixture: si el
     * `beforeEach` falla a mitad, el archivo de producción queda corrupto.
     */
    class LectorXlsxFalso extends LectorXlsx
    {
        public ?string $rutaRecibida = null;

        public function __construct(public array $filas) {}

        public function filas(string $ruta): array
        {
            $this->rutaRecibida = $ruta;

            return $this->filas;
        }
    }
}

beforeEach(function () {
    // Las filas salen del fixture real, de modo que el test sigue atado a un
    // archivo con la misma estructura que el maestro, sin tocar el original.
    $this->filas = (new LectorXlsx)->filas(base_path('tests/Fixtures/infocentros.xlsx'));

    $this->lector = new LectorXlsxFalso($this->filas);

    $this->app->instance(LectorXlsx::class, $this->lector);

    Role::findOrCreate(RoleEnum::Infocentro);
});

test('crea un infocentro por cada fila del maestro', function () {
    $this->seed(InfocentrosArchivoSeeder::class);

    expect(Infocentro::count())->toBe(3)
        ->and(Infocentro::pluck('codigo')->all())->toEqualCanonicalizing(['TST01', 'TST02', 'TST03']);
});

test('lee el maestro desde database/data', function () {
    $this->seed(InfocentrosArchivoSeeder::class);

    expect($this->lector->rutaRecibida)->toBe(database_path('data/infocentros.xlsx'));
});

test('el usuario es el código y su contraseña también', function () {
    $this->seed(InfocentrosArchivoSeeder::class);

    $user = User::where('username', 'TST01')->sole();

    expect($user->infocentro->codigo)->toBe('TST01')
        ->and(Hash::check('TST01', $user->password))->toBeTrue()
        ->and($user->getRoleNames())->toContain(RoleEnum::Infocentro->value);
});

test('el correo se deriva del código para respetar el índice único', function () {
    $this->seed(InfocentrosArchivoSeeder::class);

    // El maestro repite el nombre «Santa Inés»: el código es lo que evita el choque.
    expect(User::pluck('email')->all())->toEqualCanonicalizing([
        'tst01@infocentro.test',
        'tst02@infocentro.test',
        'tst03@infocentro.test',
    ]);
});

test('los infocentros cerrados quedan inactivos con su razón', function () {
    $this->seed(InfocentrosArchivoSeeder::class);

    $cerrado = Infocentro::where('codigo', 'TST02')->sole();
    $abierto = Infocentro::where('codigo', 'TST01')->sole();

    expect($cerrado->activo)->toBeFalse()
        ->and($cerrado->razon_cierre)->toBe('Cerrado en el archivo maestro.')
        ->and($abierto->activo)->toBeTrue()
        ->and($abierto->razon_cierre)->toBeNull();
});

test('una fila sin estatus queda activa', function () {
    $this->seed(InfocentrosArchivoSeeder::class);

    expect(Infocentro::where('codigo', 'TST03')->sole()->activo)->toBeTrue();
});

test('el catálogo conserva nombres duplicados del maestro', function () {
    $this->seed(InfocentrosArchivoSeeder::class);

    expect(Infocentro::where('nombre', 'Santa Inés')->count())->toBe(2);
});

test('volver a sembrar actualiza en lugar de duplicar', function () {
    $this->seed(InfocentrosArchivoSeeder::class);

    Infocentro::where('codigo', 'TST01')->update(['nombre' => 'Nombre Editado', 'activo' => false]);
    User::where('username', 'TST01')->update(['email' => 'editado@ejemplo.test']);

    $this->seed(InfocentrosArchivoSeeder::class);

    expect(Infocentro::count())->toBe(3)
        ->and(User::count())->toBe(3)
        ->and(Infocentro::where('codigo', 'TST01')->sole()->nombre)->toBe('Santa Inés')
        ->and(Infocentro::where('codigo', 'TST01')->sole()->activo)->toBeTrue()
        ->and(User::where('username', 'TST01')->sole()->email)->toBe('tst01@infocentro.test');
});

test('se puede entrar con el código del infocentro', function () {
    $this->seed(InfocentrosArchivoSeeder::class);

    $this->post('/login', [
        'username' => 'TST01',
        'password' => 'TST01',
    ])->assertRedirect('/infocentro/diagnostico');

    $this->assertAuthenticatedAs(User::where('username', 'TST01')->sole());
});

test('rechaza una contraseña que no sea el código', function () {
    $this->seed(InfocentrosArchivoSeeder::class);

    // En este proyecto el login es por código, así que el fallo se reporta
    // sobre `username` y no sobre `password`.
    $this->post('/login', [
        'username' => 'TST01',
        'password' => 'otra-cosa',
    ])->assertSessionHasErrors('username');

    $this->assertGuest();
});

test('se detiene si el maestro trae un código repetido', function () {
    $this->lector->filas = [
        ['cod' => 'TST01', 'nombre' => 'Uno', 'estatus' => 'Abierto'],
        ['cod' => 'TST01', 'nombre' => 'Dos', 'estatus' => 'Abierto'],
    ];

    expect(fn () => $this->seed(InfocentrosArchivoSeeder::class))
        ->toThrow(RuntimeException::class, 'El código «TST01» está repetido');
});

test('se detiene si al maestro le falta una columna obligatoria', function () {
    $this->lector->filas = [['cod' => 'TST01', 'nombre' => 'Uno']];

    expect(fn () => $this->seed(InfocentrosArchivoSeeder::class))
        ->toThrow(RuntimeException::class, 'le faltan las columnas: estatus');
});

test('se detiene si alguna fila viene sin código', function () {
    $this->lector->filas = [
        ['cod' => 'TST01', 'nombre' => 'Uno', 'estatus' => 'Abierto'],
        ['cod' => '  ', 'nombre' => 'Dos', 'estatus' => 'Abierto'],
    ];

    expect(fn () => $this->seed(InfocentrosArchivoSeeder::class))
        ->toThrow(RuntimeException::class, 'no tiene código');
});
