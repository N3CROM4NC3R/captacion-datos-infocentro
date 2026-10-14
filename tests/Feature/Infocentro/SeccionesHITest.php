<?php

use App\Enums\EstadoProyectoMincytEnum;
use App\Enums\InteresDemandaEnum;
use App\Enums\ProblemaTerritorioEnum;
use App\Models\Estado;
use App\Models\VinculacionMincyt;
use Database\Factories\InfocentroUserFactory;
use Database\Factories\VinculacionMincytFactory;

/**
 * @param  array<string, mixed>  $campos
 * @return array<string, mixed>
 */
function fichaHI(array $campos = []): array
{
    return array_merge([
        'nombre' => 'Infocentro',
        'estado_id' => Estado::factory()->create()->id,
    ], $campos);
}

/**
 * @param  array<int, array<string, mixed>>  $instituciones
 * @return array<string, mixed>
 */
function conVinculaciones(array $instituciones, array $extra = []): array
{
    $payload = fichaHI($extra);

    $payload['vinculaciones'] = [];

    foreach (['institucion', 'proyecto', 'estado', 'ultimo_contacto'] as $campo) {
        $payload['vinculaciones'][$campo] = array_column($instituciones, $campo);
    }

    return $payload;
}

test('la ficha muestra las secciones H e I', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->get('/infocentro/diagnostico')
        ->assertOk()
        ->assertSee(__('Sección H. Vinculación con instituciones del MINCYT y el Estado'))
        ->assertSee(__('Sección I. Necesidades y potencialidades del territorio'));
});

test('se guardan las instituciones vinculadas al MINCYT', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', conVinculaciones([
        [
            'institucion' => 'Instituto Venezolano de Investigaciones Científicas',
            'proyecto' => 'Red de centros comunitarios',
            'estado' => EstadoProyectoMincytEnum::Activo->value,
            'ultimo_contacto' => '2026-03-15',
        ],
        [
            'institucion' => 'Fundación Coding Kids',
            'proyecto' => null,
            'estado' => EstadoProyectoMincytEnum::Concluido->value,
            'ultimo_contacto' => null,
        ],
    ], ['vinculacion_mincyt' => '1']))->assertSessionHasNoErrors();

    $vinculos = VinculacionMincyt::orderBy('id')->get();

    expect($vinculos)->toHaveCount(2)
        ->and($vinculos[0]->institucion)->toBe('Instituto Venezolano de Investigaciones Científicas')
        ->and($vinculos[0]->estado)->toBe(EstadoProyectoMincytEnum::Activo)
        ->and($vinculos[0]->ultimo_contacto->format('Y-m-d'))->toBe('2026-03-15')
        ->and($vinculos[1]->estado)->toBe(EstadoProyectoMincytEnum::Concluido)
        ->and($vinculos[1]->proyecto)->toBeNull()
        ->and($vinculos[0]->infocentro_id)->toBe($user->infocentro->id);
});

test('no hay tope de instituciones vinculadas', function () {
    $user = InfocentroUserFactory::new()->create();

    $instituciones = array_map(fn (int $i): array => [
        'institucion' => "Institución {$i}",
        'proyecto' => "Proyecto {$i}",
        'estado' => EstadoProyectoMincytEnum::Activo->value,
        'ultimo_contacto' => null,
    ], range(1, 5));

    $this->actingAs($user)->put('/infocentro/diagnostico', conVinculaciones($instituciones, ['vinculacion_mincyt' => '1']))
        ->assertSessionHasNoErrors();

    expect(VinculacionMincyt::count())->toBe(5);
});

test('declarar vinculación con el MINCYT exige al menos una institución', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', fichaHI([
        'vinculacion_mincyt' => '1',
    ]))->assertSessionHasErrors('vinculaciones.institucion');
});

test('sin vinculación declarada no se exigen instituciones', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', fichaHI())->assertSessionHasNoErrors();

    expect(VinculacionMincyt::count())->toBe(0);
});

test('el estado del proyecto fuera del enum es rechazado', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', conVinculaciones([
        [
            'institucion' => 'Instituto',
            'proyecto' => 'Proyecto',
            'estado' => 'pausado',
            'ultimo_contacto' => null,
        ],
    ], ['vinculacion_mincyt' => '1']))->assertSessionHasErrors('vinculaciones.estado.0');
});

test('una fecha de contacto inválida es rechazada', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', conVinculaciones([
        [
            'institucion' => 'Instituto',
            'proyecto' => 'Proyecto',
            'estado' => EstadoProyectoMincytEnum::Activo->value,
            'ultimo_contacto' => 'no es una fecha',
        ],
    ], ['vinculacion_mincyt' => '1']))->assertSessionHasErrors('vinculaciones.ultimo_contacto.0');
});

test('las instituciones se reemplazan al volver a guardar', function () {
    $user = InfocentroUserFactory::new()->create();
    VinculacionMincytFactory::new()->for($user->infocentro)->create(['institucion' => 'Obsoleta']);

    $this->actingAs($user)->put('/infocentro/diagnostico', conVinculaciones([
        [
            'institucion' => 'Vigente',
            'proyecto' => 'Proyecto nuevo',
            'estado' => EstadoProyectoMincytEnum::Inactivo->value,
            'ultimo_contacto' => null,
        ],
    ], ['vinculacion_mincyt' => '1']))->assertSessionHasNoErrors();

    expect(VinculacionMincyt::where('institucion', 'Obsoleta')->exists())->toBeFalse()
        ->and(VinculacionMincyt::where('institucion', 'Vigente')->exists())->toBeTrue();
});

test('el acompañamiento de Fundacite exige su detalle', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', fichaHI([
        'acomp_fundacite' => '1',
    ]))->assertSessionHasErrors('acomp_fundacite_detalle');

    $this->actingAs($user)->put('/infocentro/diagnostico', fichaHI([
        'acomp_fundacite' => '1',
        'acomp_fundacite_detalle' => 'Capacitación en robótica',
    ]))->assertSessionHasNoErrors();

    expect($user->infocentro->refresh()->acomp_fundacite)->toBeTrue();
});

test('se guardan los problemas prioritarios del territorio', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', fichaHI([
        'problemas' => [
            ProblemaTerritorioEnum::Ambientales->value,
            ProblemaTerritorioEnum::Energia->value,
        ],
        'saberes_recursos' => 'Cestería y medicina tradicional',
        'interes_jovenes' => InteresDemandaEnum::SiMuchos->value,
        'interes_adultos_mayores' => InteresDemandaEnum::Pocos->value,
    ]))->assertSessionHasNoErrors();

    $infocentro = $user->infocentro->refresh();

    expect($infocentro->problemas)->toBe(['ambientales', 'energia'])
        ->and($infocentro->saberes_recursos)->toBe('Cestería y medicina tradicional')
        ->and($infocentro->interes_jovenes)->toBe(InteresDemandaEnum::SiMuchos)
        ->and($infocentro->interes_adultos_mayores)->toBe(InteresDemandaEnum::Pocos);
});

test('marcar "otros" problemas exige especificarlos', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', fichaHI([
        'problemas' => [ProblemaTerritorioEnum::Otros->value],
    ]))->assertSessionHasErrors('problemas_otros');

    $this->actingAs($user)->put('/infocentro/diagnostico', fichaHI([
        'problemas' => [ProblemaTerritorioEnum::Otros->value],
        'problemas_otros' => 'Falta de transporte',
    ]))->assertSessionHasNoErrors();

    expect($user->infocentro->refresh()->problemas_otros)->toBe('Falta de transporte');
});

test('marcar otros problemas sin "otros" no exige el detalle', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', fichaHI([
        'problemas' => [ProblemaTerritorioEnum::Riesgo->value],
    ]))->assertSessionHasNoErrors();
});

test('un problema fuera del enum es rechazado', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', fichaHI([
        'problemas' => ['climatico'],
    ]))->assertSessionHasErrors('problemas.0');
});

test('las experiencias de ciencia ciudadana exigen su detalle', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', fichaHI([
        'ciencia_ciudadana' => '1',
    ]))->assertSessionHasErrors('ciencia_ciudadana_detalle');

    $this->actingAs($user)->put('/infocentro/diagnostico', fichaHI([
        'ciencia_ciudadana' => '1',
        'ciencia_ciudadana_detalle' => 'Mapeo participativo de quebradas',
    ]))->assertSessionHasNoErrors();

    expect($user->infocentro->refresh()->ciencia_ciudadana)->toBeTrue();
});

test('la ficha muestra los datos de H e I ya guardados', function () {
    $user = InfocentroUserFactory::new()->create();

    $user->infocentro->update([
        'vinculacion_mincyt' => true,
        'problemas' => ['salud'],
        'interes_jovenes' => 'si_algunos',
        'ciencia_ciudadana' => true,
        'ciencia_ciudadana_detalle' => 'Mapeo participativo',
    ]);

    VinculacionMincytFactory::new()->for($user->infocentro)->create([
        'institucion' => 'Instituto Guardado',
    ]);

    $this->actingAs($user)->get('/infocentro/diagnostico')
        ->assertOk()
        ->assertSee('Instituto Guardado')
        ->assertSee('Mapeo participativo');
});

test('un usuario de infocentro no puede tocar las instituciones de otro', function () {
    $user = InfocentroUserFactory::new()->create();
    $otro = InfocentroUserFactory::new()->create();

    VinculacionMincytFactory::new()->for($otro->infocentro)->create(['institucion' => 'Institución del otro']);

    $this->actingAs($user)->put('/infocentro/diagnostico', conVinculaciones([
        [
            'institucion' => 'Institución propia',
            'proyecto' => 'P',
            'estado' => EstadoProyectoMincytEnum::Activo->value,
            'ultimo_contacto' => null,
        ],
    ], ['vinculacion_mincyt' => '1']))->assertSessionHasNoErrors();

    expect(VinculacionMincyt::where('institucion', 'Institución del otro')->exists())->toBeTrue()
        ->and(VinculacionMincyt::where('infocentro_id', $user->infocentro->id)->count())->toBe(1);
});
