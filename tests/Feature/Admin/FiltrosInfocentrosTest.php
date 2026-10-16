<?php

use App\Enums\DimensionPerfilamientoEnum;
use App\Enums\TipoInfocentroEnum;
use App\Models\Estado;
use App\Models\Infocentro;
use Database\Factories\AdminFactory;
use Database\Factories\InfocentroUserFactory;
use Illuminate\Support\Str;

/**
 * El HTML sin etiquetas, con los espacios normalizados.
 *
 * Para comprobar frases que cruzan varios elementos: en el HTML en bruto un
 * «7 de 10 infocentros» viene partido por el markup y no aparece junto.
 */
function textoPlano(string $html): string
{
    return trim((string) preg_replace('/\s+/u', ' ', strip_tags($html)));
}

/**
 * Un infocentro con la ficha guardada, para que tenga clasificación.
 *
 * @param  array<string, mixed>  $atributos
 */
function infocentroClasificado(string $clasificacion, array $atributos = []): Infocentro
{
    $infocentro = Infocentro::factory()->create($atributos);
    $usuario = InfocentroUserFactory::new()->forInfocentro($infocentro)->create();

    // El tipo no lo pone nadie: lo deduce el controller al guardar. Con las siete
    // dimensiones a 3 sale 21 y tipo A; a 2 sale 14 y tipo B; a 0, tipo C.
    $valor = match ($clasificacion) {
        'a' => '3',
        'b' => '2',
        default => '0',
    };

    test()->actingAs($usuario)->put('/infocentro/diagnostico', [
        'nombre' => $infocentro->nombre,
        'estado_id' => $infocentro->estado_id,
        'activo' => '1',
        'puntuaciones' => collect(DimensionPerfilamientoEnum::cases())
            ->mapWithKeys(fn ($dimension): array => [$dimension->value => $valor])
            ->all(),
    ])->assertSessionHasNoErrors();

    return $infocentro->refresh();
}

test('filtrar por nombre acota al infocentro que lo lleva', function () {
    $admin = AdminFactory::new()->create();
    $buscado = Infocentro::factory()->create(['nombre' => 'Nodo社区卫生 alpha']);
    Infocentro::factory()->create(['nombre' => 'Otro Completely distinto']);

    $this->actingAs($admin)
        ->get('/admin/infocentros?nombre='.urlencode('alpha'))
        ->assertOk()
        ->assertSee($buscado->nombre)
        ->assertDontSee('Otro completamente');
});

test('filtrar por nombre no trae los que solo coinciden por código', function () {
    $admin = AdminFactory::new()->create();
    $porNombre = Infocentro::factory()->create(['nombre' => 'Nodo Aldea Norte', 'codigo' => 'AAA']);
    $porCodigo = Infocentro::factory()->create(['nombre' => 'Lugar Cero', 'codigo' => 'NODO-ALDEA']);

    // Antes el buscador `q` mezclaba las dos columnas y no se distinguía; ahora
    // cada campo busca en la suya.
    $this->actingAs($admin)
        ->get('/admin/infocentros?nombre='.urlencode('NODO'))
        ->assertOk()
        ->assertSee($porNombre->nombre)
        ->assertDontSee($porCodigo->nombre);

    $this->actingAs($admin)
        ->get('/admin/infocentros?codigo=NODO')
        ->assertOk()
        ->assertSee($porCodigo->nombre)
        ->assertDontSee($porNombre->nombre);
});

test('filtrar por nombre ignora mayúsculas y acentos no importan', function () {
    $admin = AdminFactory::new()->create();
    $buscado = Infocentro::factory()->create(['nombre' => 'Nodo COMUNITARIO']);

    $this->actingAs($admin)
        ->get('/admin/infocentros?nombre='.urlencode('comunitario'))
        ->assertOk()
        ->assertSee($buscado->nombre);
});

test('filtrar por estado territorial acota', function () {
    $admin = AdminFactory::new()->create();
    $estado = Estado::factory()->create(['nombre' => 'Delta Depuración']);
    $otro = Estado::factory()->create();

    $dentro = Infocentro::factory()->create(['nombre' => 'Nodo Del Estado Delta', 'estado_id' => $estado->id]);
    $fuera = Infocentro::factory()->create(['nombre' => 'Nodo De Otro Estado', 'estado_id' => $otro->id]);

    // El nombre del otro estado sí aparece, en el desplegable: lo que se
    // comprueba es qué filas salen en la tabla.
    $this->actingAs($admin)
        ->get("/admin/infocentros?estado={$estado->id}")
        ->assertOk()
        ->assertSee($dentro->nombre)
        ->assertDontSee($fuera->nombre);
});

test('filtrar por estatus distingue activo de inactivo', function () {
    $admin = AdminFactory::new()->create();
    $activo = Infocentro::factory()->create(['nombre' => 'Centro Operativo Activo', 'activo' => true]);
    $inactivo = Infocentro::factory()->create(['nombre' => 'Centro Apagado Inactivo', 'activo' => false]);

    $this->actingAs($admin)
        ->get('/admin/infocentros?estatus=activo')
        ->assertOk()
        ->assertSee($activo->nombre)
        ->assertDontSee($inactivo->nombre);

    $this->actingAs($admin)
        ->get('/admin/infocentros?estatus=inactivo')
        ->assertOk()
        ->assertSee($inactivo->nombre)
        ->assertDontSee($activo->nombre);
});

test('filtrar por clasificación devuelve solo los de ese tipo', function () {
    $admin = AdminFactory::new()->create();
    $tipoA = infocentroClasificado('a', ['nombre' => 'Piloto De Referencia']);
    $tipoB = infocentroClasificado('b', ['nombre' => 'En Consolidacion']);

    expect($tipoA->tipo_infocentro)->toBe(TipoInfocentroEnum::A)
        ->and($tipoB->tipo_infocentro)->toBe(TipoInfocentroEnum::B);

    $this->actingAs($admin)
        ->get('/admin/infocentros?clasificacion=a')
        ->assertOk()
        ->assertSee($tipoA->nombre)
        ->assertDontSee($tipoB->nombre);
});

test('la clasificación solo existe en fichas guardadas', function () {
    $admin = AdminFactory::new()->create();
    $conFicha = infocentroClasificado('a', ['nombre' => 'Clasificado ComoA']);
    $sinFicha = Infocentro::factory()->create(['nombre' => 'Sin Clasificar ComoA']);

    // `tipo_infocentro` lo calcula el perfilamiento al guardar, así que este
    // filtro nunca devuelve un infocentro sin ficha. No es un fallo: es lo que
    // significa clasificar.
    $this->actingAs($admin)
        ->get('/admin/infocentros?clasificacion=a')
        ->assertOk()
        ->assertSee($conFicha->nombre)
        ->assertDontSee($sinFicha->nombre);
});

test('los filtros se combinan', function () {
    $admin = AdminFactory::new()->create();
    $estado = Estado::factory()->create();

    $coincide = Infocentro::factory()->create([
        'nombre' => 'Nodo Compartido Norte',
        'estado_id' => $estado->id,
        'activo' => true,
    ]);

    // Coincide con el nombre y el estado, pero no con el estatus.
    Infocentro::factory()->create([
        'nombre' => 'Nodo Compartido Sur',
        'estado_id' => $estado->id,
        'activo' => false,
    ]);

    $this->actingAs($admin)
        ->get("/admin/infocentros?nombre=compartido&estado={$estado->id}&estatus=activo")
        ->assertOk()
        ->assertSee($coincide->nombre)
        ->assertDontSee('Nodo compartido sur');
});

test('los valores imposibles de los desplegables se ignoran', function () {
    $admin = AdminFactory::new()->create();
    $infocentro = Infocentro::factory()->create(['nombre' => 'Visible Con Filtros Raros', 'activo' => true]);

    // Una URL manipulada con valores que no existen no puede filtrar de más ni
    // reventar la consulta: se ignoran y sale el listado entero.
    $this->actingAs($admin)
        ->get('/admin/infocentros?estado=99999&estatus=quedado&clasificacion=z')
        ->assertOk()
        ->assertSee($infocentro->nombre);
});

test('el enlace de limpiar solo aparece cuando hay algo que limpiar', function () {
    $admin = AdminFactory::new()->create();
    Infocentro::factory()->count(2)->create();

    $this->actingAs($admin)
        ->get('/admin/infocentros')
        ->assertDontSee(__('Limpiar'));

    $this->actingAs($admin)
        ->get('/admin/infocentros?estatus=activo')
        ->assertSee(__('Limpiar'));

    $this->actingAs($admin)
        ->get('/admin/infocentros?nombre=inexistente')
        ->assertSee(__('Limpiar'));
});

test('la cabecera dice cuántos salen de los filtros', function () {
    $admin = AdminFactory::new()->create();
    Infocentro::factory()->count(7)->create(['activo' => true]);
    Infocentro::factory()->count(3)->create(['activo' => false]);

    // Sin filtros no se anuncia el recuento: solo están los totales.
    $sinFiltros = $this->actingAs($admin)->get('/admin/infocentros')->assertOk()->getContent();

    expect(textoPlano($sinFiltros))->not->toContain(__('coinciden con los filtros.'));

    // Con el filtro puesto, el recuento va con su propia frase. Se compara
    // sobre el texto plano porque en el HTML los números y las palabras están
    // en elementos separados y la frase no aparece junta.
    $conFiltros = $this->actingAs($admin)->get('/admin/infocentros?estatus=activo')->assertOk()->getContent();

    expect(textoPlano($conFiltros))->toContain(__('7 de 10 infocentros coinciden con los filtros.'));
});

test('la paginación conserva los filtros', function () {
    $admin = AdminFactory::new()->create();
    // Más de quince filas, y todas con «nodo» en el nombre, que es el corte de
    // página: si no, no hay enlaces y el test no comprueba nada.
    Infocentro::factory()->count(20)->create([
        'nombre' => 'Nodo Numeral '.Str::random(8),
        'activo' => true,
    ]);

    $html = $this->actingAs($admin)->get('/admin/infocentros?estatus=activo&nombre=nodo')->getContent();

    expect($html)->toContain('/admin/infocentros?estatus=activo&amp;nombre=nodo&amp;page=2');
});

test('las tres listas de filtros no se desincronizan entre sí', function () {
    $controller = file_get_contents(app_path('Http/Controllers/Admin/InfocentroController.php'));
    $fichas = file_get_contents(app_path('Http/Controllers/Admin/FichasController.php'));
    $js = file_get_contents(resource_path('js/app.js'));
    $vista = file_get_contents(resource_path('views/admin/infocentros/index.blade.php'));

    preg_match("/FILTROS_LISTADO = \[(.*?)\]/", $controller, $delController);
    preg_match("/'infocentros' => \[.*?'filtros' => \[(.*?)\]/s", $fichas, $deFichas);
    preg_match("/const filtros = \[(.*?)\]/", $js, $delJs);

    $normalizar = fn (string $lista): array => array_map(
        fn (string $clave): string => trim($clave, " \t\n\r\0\x0B'\""),
        array_filter(explode(',', $lista), fn (string $clave): bool => trim($clave) !== ''),
    );

    $esperados = ['nombre', 'codigo', 'estado', 'estatus', 'clasificacion', 'ficha', 'page'];

    // Si una de las tres se queda corta, borrar un infocentro devuelve al listado
    // sin filtros y sin ningún error visible: la única pista es que el enlace
    // «Eliminar la ficha» tampoco arrastra ese filtro.
    expect($normalizar($delController[1] ?? ''))->toBe($esperados)
        ->and($normalizar($deFichas[1] ?? ''))->toBe($esperados)
        ->and($normalizar($delJs[1] ?? ''))->toBe($esperados);

    // Y el enlace de «Eliminar la ficha» sale de este listado, así que arrastra
    // los mismos nombres.
    foreach ($esperados as $clave) {
        expect($vista)->toContain("'{$clave}' =>");
    }
});
