<?php

use App\Enums\CargoFirmaEnum;
use App\Models\Estado;
use Database\Factories\InfocentroUserFactory;
use Illuminate\Testing\TestResponse;

/**
 * Guardar la ficha con el bloque de firmas, como la envía el navegador.
 *
 * @param  array<string, mixed>  $datos
 */
function guardarConFirmas($user, array $firmas = []): TestResponse
{
    $estado = Estado::factory()->create();

    return test()->actingAs($user)->post('/infocentro/diagnostico', [
        '_method' => 'PUT',
        'nombre' => 'Infocentro',
        'estado_id' => $estado->id,
        'activo' => '1',
        'firmas' => $firmas,
    ]);
}

test('el bloque de firmas reproduce los cuatro cargos del PDF', function () {
    $html = $this->actingAs(InfocentroUserFactory::new()->create())
        ->get('/infocentro/diagnostico')
        ->assertOk()
        ->getContent();

    foreach (CargoFirmaEnum::cases() as $cargo) {
        expect(str_contains($html, htmlspecialchars($cargo->label(), ENT_QUOTES)))->toBeTrue(
            "Falta el cargo «{$cargo->label()}» en el bloque de firmas.",
        );
    }
});

test('cada persona tiene nombre, firma y fecha uno al lado del otro', function () {
    $html = $this->actingAs(InfocentroUserFactory::new()->create())
        ->get('/infocentro/diagnostico')
        ->assertOk()
        ->getContent();

    foreach (CargoFirmaEnum::cases() as $cargo) {
        foreach (['nombre', 'firma', 'fecha'] as $campo) {
            $name = 'firmas['.$cargo->value.']['.$campo.']';

            expect(str_contains($html, 'name="'.$name.'"'))->toBeTrue(
                "Falta el campo «{$campo}» de «{$cargo->label()}».",
            );
        }
    }

    // Nombre, firma y fecha comparten la misma fila, como en el original.
    expect(str_contains($html, 'sm:grid-cols-3'))->toBeTrue(
        'Los tres campos de cada persona deben ir en la misma fila.',
    );
});

test('el campo de fecha se pinta como fecha y no como texto', function () {
    $html = $this->actingAs(InfocentroUserFactory::new()->create())
        ->get('/infocentro/diagnostico')
        ->assertOk()
        ->getContent();

    expect(str_contains($html, 'name="firmas[diagnosticador][fecha]" type="date"'))->toBeTrue(
        'La fecha de la firma debe usar un control de tipo date.',
    );
});

test('las firmas se guardan', function () {
    $user = InfocentroUserFactory::new()->create();

    guardarConFirmas($user, [
        CargoFirmaEnum::Diagnosticador->value => [
            'nombre' => 'Ana Pérez',
            'firma' => 'Ana Pérez',
            'fecha' => '2026-10-15',
        ],
        CargoFirmaEnum::VoceroSac->value => [
            'nombre' => 'Luis Gómez',
            'firma' => 'Luis Gómez',
            'fecha' => '2026-10-15',
        ],
    ])->assertSessionHasNoErrors();

    $firmas = $user->infocentro->refresh()->firmas;

    expect($firmas)->toHaveKeys(['diagnosticador', 'vocero_sac'])
        ->and($firmas['diagnosticador'])->toBe([
            'nombre' => 'Ana Pérez',
            'firma' => 'Ana Pérez',
            'fecha' => '2026-10-15',
        ])
        // Quien no firmó no deja una entrada vacía en la base de datos.
        ->and($firmas)->not->toHaveKey('facilitador');
});

test('las firmas vuelven a pintarse al recargar la ficha', function () {
    $user = InfocentroUserFactory::new()->create();

    guardarConFirmas($user, [
        CargoFirmaEnum::ConsejoComunal->value => [
            'nombre' => 'Rosa Díaz',
            'firma' => 'Rosa Díaz',
            'fecha' => '2026-09-01',
        ],
    ])->assertSessionHasNoErrors();

    $this->actingAs($user)->get('/infocentro/diagnostico')
        ->assertOk()
        ->assertSee('Rosa Díaz')
        ->assertSee('name="firmas[consejo_comunal][fecha]" type="date" value="2026-09-01"', escape: false);
});

test('un bloque de firmas vacío se guarda como arreglo vacío', function () {
    $user = InfocentroUserFactory::new()->create();

    guardarConFirmas($user, [])->assertSessionHasNoErrors();

    expect($user->infocentro->refresh()->firmas)->toBe([]);
});

test('se rechazan las fechas que no son fechas', function () {
    $user = InfocentroUserFactory::new()->create();

    guardarConFirmas($user, [
        CargoFirmaEnum::Facilitador->value => [
            'nombre' => 'Carlos Ruiz',
            'fecha' => 'el martes que viene',
        ],
    ])->assertSessionHasErrors('firmas.facilitador.fecha');
});

test('un cargo de firma desconocido se guarda vacío sin romper nada', function () {
    $user = InfocentroUserFactory::new()->create();

    guardarConFirmas($user, [
        'inventado' => ['nombre' => 'Nadie', 'firma' => 'Nadie', 'fecha' => '2026-01-01'],
        CargoFirmaEnum::Diagnosticador->value => ['nombre' => 'Ana Pérez'],
    ])->assertSessionHasNoErrors();

    $firmas = $user->infocentro->refresh()->firmas;

    expect($firmas)->toBe(['diagnosticador' => ['nombre' => 'Ana Pérez']]);
});
