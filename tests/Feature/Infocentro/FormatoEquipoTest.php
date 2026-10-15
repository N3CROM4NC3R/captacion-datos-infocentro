<?php

use App\Enums\FormatoEquipoEnum;
use App\Enums\TipoEquipoEnum;
use App\Models\Equipo;
use App\Models\Estado;
use Database\Factories\InfocentroUserFactory;

/**
 * @param  array<string, mixed>  $equipos
 * @return array<string, mixed>
 */
function fichaConEquipos(array $equipos): array
{
    return [
        'nombre' => 'Infocentro',
        'estado_id' => Estado::factory()->create()->id,
        'activo' => '1',
        'equipos' => $equipos,
    ];
}

test('se guarda el formato de las computadoras', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', fichaConEquipos([
        'cantidad' => ['computadoras' => 12],
        'formato' => ['computadoras' => FormatoEquipoEnum::Ambas->value],
    ]))->assertSessionHasNoErrors();

    $equipo = Equipo::where('tipo', TipoEquipoEnum::Computadoras)->sole();

    expect($equipo->formato)->toBe(FormatoEquipoEnum::Ambas)
        ->and($equipo->cantidad)->toBe(12);
});

test('el formato se repueblan al recargar la ficha', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', fichaConEquipos([
        'cantidad' => ['computadoras' => 12],
        'formato' => ['computadoras' => FormatoEquipoEnum::Portatiles->value],
    ]))->assertSessionHasNoErrors();

    $html = $this->actingAs($user)->get('/infocentro/diagnostico')
        ->assertOk()
        ->getContent();

    expect($html)->toContain('name="equipos[formato][computadoras]"');

    $marcada = null;

    if (preg_match('/<select[^>]*name="equipos\[formato\]\[computadoras\]"[^>]*>(.*?)<\/select>/s', $html, $select)) {
        preg_match_all('/<option\b([^>]*)>/', $select[1], $opciones);

        foreach ($opciones[1] as $opcion) {
            if (preg_match('/\bselected\b/', $opcion) && preg_match('/value="([^"]*)"/', $opcion, $valor)) {
                $marcada = $valor[1];
            }
        }
    }

    expect($marcada)->toBe(FormatoEquipoEnum::Portatiles->value);
});

test('un formato fuera del enum es rechazado', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', fichaConEquipos([
        'cantidad' => ['computadoras' => 12],
        'formato' => ['computadoras' => 'tablets'],
    ]))->assertSessionHasErrors('equipos.formato.computadoras');
});

test('una clave de tipo inventada en el formato se rechaza', function () {
    $user = InfocentroUserFactory::new()->create();

    // `validarClaves()` agrupa las claves de toda la sección equipos y reporta
    // siempre contra `equipos.estado`, que es su campo de referencia.
    $this->actingAs($user)->put('/infocentro/diagnostico', fichaConEquipos([
        'cantidad' => ['inventado' => 3],
        'formato' => ['inventado' => FormatoEquipoEnum::Ambas->value],
    ]))->assertSessionHasErrors('equipos.estado.inventado');
});

test('el formato de un tipo que sí existe no dispara error de clave', function () {
    $user = InfocentroUserFactory::new()->create();

    // Telescopios es un tipo válido: la regla lo acepta y lo descarta el
    // controller, que es el comportamiento que se quiere.
    $this->actingAs($user)->put('/infocentro/diagnostico', fichaConEquipos([
        'cantidad' => ['telescopios_portatiles' => 3],
        'formato' => ['telescopios_portatiles' => FormatoEquipoEnum::Ambas->value],
    ]))->assertSessionHasNoErrors();
});

test('el formato es opcional', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', fichaConEquipos([
        'cantidad' => ['computadoras' => 12],
    ]))->assertSessionHasNoErrors();

    expect(Equipo::where('tipo', TipoEquipoEnum::Computadoras)->sole()->formato)->toBeNull();
});

test('el formato solo se ofrece para las computadoras', function () {
    $user = InfocentroUserFactory::new()->create();

    $html = $this->actingAs($user)->get('/infocentro/diagnostico')
        ->assertOk()
        ->getContent();

    $ofrecidos = [];

    foreach (TipoEquipoEnum::cases() as $tipo) {
        if (str_contains($html, "name=\"equipos[formato][{$tipo->value}]\"")) {
            $ofrecidos[] = $tipo->value;
        }
    }

    expect($ofrecidos)->toBe([TipoEquipoEnum::Computadoras->value]);
});

test('el formato de otro tipo no se guarda aunque venga en el payload', function () {
    $user = InfocentroUserFactory::new()->create();

    // La regla lo acepta porque el enum es válido, pero solo computadoras lo
    // pregunta: el controller debe descartar el resto.
    $this->actingAs($user)->put('/infocentro/diagnostico', fichaConEquipos([
        'cantidad' => ['telescopios_portatiles' => 3],
        'formato' => ['telescopios_portatiles' => FormatoEquipoEnum::Ambas->value],
    ]))->assertSessionHasNoErrors();

    expect(Equipo::where('tipo', TipoEquipoEnum::TelescopiosPortatiles)->sole()->formato)->toBeNull();
});

test('el enum expone las tres opciones con su etiqueta', function () {
    expect(FormatoEquipoEnum::options())->toBe([
        'escritorio' => 'De escritorio',
        'portatiles' => 'Portátiles',
        'ambas' => 'De escritorio y portátiles',
    ]);
});

test('solo las computadoras declaran que preguntan por el formato', function () {
    $conFormato = array_map(
        fn (TipoEquipoEnum $t): string => $t->value,
        array_filter(TipoEquipoEnum::cases(), fn (TipoEquipoEnum $t): bool => $t->tieneFormato())
    );

    expect($conFormato)->toBe([TipoEquipoEnum::Computadoras->value]);
});
