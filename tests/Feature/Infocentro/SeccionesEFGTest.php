<?php

use App\Enums\ConsultasPopularesEnum;
use App\Enums\EstadoEquipoEnum;
use App\Enums\FrecuenciaEnum;
use App\Enums\MesaTecnicaEnum;
use App\Enums\NivelOfimaticaEnum;
use App\Enums\OfertaAnualEnum;
use App\Enums\ParticipacionComunidadEnum;
use App\Enums\SuficienciaEquipamientoEnum;
use App\Enums\TipoActividadEnum;
use App\Enums\TipoEnergiaEnum;
use App\Enums\TipoEquipoEnum;
use App\Enums\VinculacionEnum;
use App\Models\Actividad as ActividadModel;
use App\Models\Energia;
use App\Models\Equipo;
use App\Models\Estado;
use Database\Factories\InfocentroUserFactory;

/**
 * @param  array<string, mixed>  $campos
 * @return array<string, mixed>
 */
function fichaExtendida(array $campos = []): array
{
    $estado = Estado::factory()->create();

    return array_merge([
        'nombre' => 'Infocentro',
        'estado_id' => $estado->id,
        'activo' => '1',
    ], $campos);
}

test('la ficha muestra las secciones E, F y G', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->get('/infocentro/diagnostico')
        ->assertOk()
        ->assertSee(__('Sección E. Modelo de gestión y articulación comunitaria'))
        ->assertSee(__('Sección F. Actividades y oferta formativa'))
        ->assertSee(__('Sección G. Infraestructura y equipamiento'));
});

test('se guardan los datos de la sección E', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', fichaExtendida([
        'vinculacion_sac' => VinculacionEnum::Activa->value,
        'vinculacion_sac_desde' => '2024',
        'vinculacion_sac_proyectos' => 'Plan de alfabetización',
        'vinculacion_consejos' => VinculacionEnum::Pasiva->value,
        'consejos_cantidad' => 3,
        'vinculacion_consejos_proyectos' => 'Jornadas de reparación',
        'vinculacion_autoridad' => '1',
        'autoridad_institucion' => 'Gobernación de Miranda',
        'autoridad_proyecto' => 'Convenio de capacitación',
        'consultas_populares' => ConsultasPopularesEnum::Activa->value,
        'consultas_populares_detalle' => 'Consulta del sector 4',
        'mesa_tecnica' => MesaTecnicaEnum::Participa->value,
        'participacion_comunidad' => ParticipacionComunidadEnum::Asambleas->value,
        'observaciones_gestion' => 'Buen trabajo conjunto',
    ]))->assertSessionHasNoErrors();

    $infocentro = $user->infocentro->refresh();

    expect($infocentro->vinculacion_sac)->toBe(VinculacionEnum::Activa)
        ->and($infocentro->vinculacion_sac_desde)->toBe('2024')
        ->and($infocentro->vinculacion_consejos)->toBe(VinculacionEnum::Pasiva)
        ->and($infocentro->consejos_cantidad)->toBe(3)
        ->and($infocentro->vinculacion_autoridad)->toBeTrue()
        ->and($infocentro->autoridad_institucion)->toBe('Gobernación de Miranda')
        ->and($infocentro->consultas_populares)->toBe(ConsultasPopularesEnum::Activa)
        ->and($infocentro->mesa_tecnica)->toBe(MesaTecnicaEnum::Participa)
        ->and($infocentro->participacion_comunidad)->toBe(ParticipacionComunidadEnum::Asambleas);
});

test('la fecha de vinculación con la SAC es obligatoria si está vinculado', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', fichaExtendida([
        'vinculacion_sac' => VinculacionEnum::Activa->value,
    ]))->assertSessionHasErrors('vinculacion_sac_desde');
});

test('la fecha de vinculación no se exige si no está vinculado', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', fichaExtendida([
        'vinculacion_sac' => VinculacionEnum::Ninguna->value,
    ]))->assertSessionHasNoErrors();
});

test('la institución es obligatoria si hay vinculación con la autoridad regional', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', fichaExtendida([
        'vinculacion_autoridad' => '1',
    ]))->assertSessionHasErrors('autoridad_institucion');
});

test('el detalle de consultas populares es obligatorio si participa', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', fichaExtendida([
        'consultas_populares' => ConsultasPopularesEnum::Limitada->value,
    ]))->assertSessionHasErrors('consultas_populares_detalle');
});

test('se guardan las actividades marcadas', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', fichaExtendida([
        'actividades' => [
            'activa' => [
                TipoActividadEnum::Robotica->value => '1',
                TipoActividadEnum::Astronomia->value => '1',
                TipoActividadEnum::Ofimatica->value => '1',
            ],
            'frecuencia' => [
                TipoActividadEnum::Robotica->value => FrecuenciaEnum::Semanal->value,
                TipoActividadEnum::Astronomia->value => FrecuenciaEnum::Mensual->value,
                TipoActividadEnum::Ofimatica->value => FrecuenciaEnum::Quincenal->value,
            ],
            'participantes' => [
                TipoActividadEnum::Robotica->value => 15,
                TipoActividadEnum::Astronomia->value => 8,
                TipoActividadEnum::Ofimatica->value => 22,
            ],
            'detalle' => [TipoActividadEnum::Robotica->value => 'Arduino'],
            'opcion_si_no' => [TipoActividadEnum::Astronomia->value => '1'],
            'opcion_nivel' => [TipoActividadEnum::Ofimatica->value => NivelOfimaticaEnum::Intermedio->value],
        ],
    ]))->assertSessionHasNoErrors();

    $actividades = ActividadModel::orderBy('tipo')->get()->keyBy(fn ($a) => $a->tipo->value);

    expect($actividades)->toHaveCount(3)
        ->and($actividades['robotica']->frecuencia)->toBe(FrecuenciaEnum::Semanal)
        ->and($actividades['robotica']->participantes)->toBe(15)
        ->and($actividades['robotica']->detalle)->toBe('Arduino')
        ->and($actividades['astronomia']->opcion_si_no)->toBeTrue()
        ->and($actividades['ofimatica']->opcion_nivel)->toBe(NivelOfimaticaEnum::Intermedio);
});

test('las actividades no marcadas no se guardan', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', fichaExtendida([
        'actividades' => [
            'activa' => [TipoActividadEnum::Robotica->value => '1'],
            'frecuencia' => [TipoActividadEnum::Robotica->value => FrecuenciaEnum::Diaria->value],
            'participantes' => [TipoActividadEnum::Robotica->value => 5],
        ],
    ]))->assertSessionHasNoErrors();

    expect(ActividadModel::count())->toBe(1)
        ->and(ActividadModel::first()->tipo)->toBe(TipoActividadEnum::Robotica);
});

test('las actividades se reemplazan al volver a guardar', function () {
    $user = InfocentroUserFactory::new()->create();
    ActividadModel::factory()->for($user->infocentro)->create([
        'tipo' => TipoActividadEnum::CienciasBasicas,
    ]);

    $this->actingAs($user)->put('/infocentro/diagnostico', fichaExtendida([
        'actividades' => [
            'activa' => [TipoActividadEnum::Robotica->value => '1'],
            'frecuencia' => [TipoActividadEnum::Robotica->value => FrecuenciaEnum::Diaria->value],
            'participantes' => [TipoActividadEnum::Robotica->value => 5],
        ],
    ]))->assertSessionHasNoErrors();

    expect(ActividadModel::count())->toBe(1)
        ->and(ActividadModel::first()->tipo)->toBe(TipoActividadEnum::Robotica);
});

test('una actividad activa exige frecuencia y participantes', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', fichaExtendida([
        'actividades' => ['activa' => [TipoActividadEnum::Robotica->value => '1']],
    ]))->assertSessionHasErrors([
        'actividades.frecuencia.robotica',
        'actividades.participantes.robotica',
    ]);
});

test('otras actividades exigen su nombre', function () {
    $user = InfocentroUserFactory::new()->create();

    $payload = [
        'actividades' => [
            'activa' => [TipoActividadEnum::Otras->value => '1'],
            'frecuencia' => [TipoActividadEnum::Otras->value => FrecuenciaEnum::Esporadica->value],
            'participantes' => [TipoActividadEnum::Otras->value => 10],
        ],
    ];

    $this->actingAs($user)->put('/infocentro/diagnostico', fichaExtendida($payload))
        ->assertSessionHasErrors('actividades.detalle.otras');

    $payload['actividades']['detalle'] = [TipoActividadEnum::Otras->value => 'Club de lectura'];

    $this->actingAs($user)->put('/infocentro/diagnostico', fichaExtendida($payload))
        ->assertSessionHasNoErrors();

    expect(ActividadModel::first()->detalle)->toBe('Club de lectura');
});

test('se guarda la oferta formativa anual con su responsable', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', fichaExtendida([
        'oferta_formativa_anual' => OfertaAnualEnum::Parcial->value,
        'oferta_formativa_define' => 'Ministerio del Poder Popular',
    ]))->assertSessionHasNoErrors();

    expect($user->infocentro->refresh()->oferta_formativa_anual)->toBe(OfertaAnualEnum::Parcial);
});

test('la oferta formativa exige responsable si no es "no"', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', fichaExtendida([
        'oferta_formativa_anual' => OfertaAnualEnum::Si->value,
    ]))->assertSessionHasErrors('oferta_formativa_define');
});

test('se guarda el inventario de equipos', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', fichaExtendida([
        'equipos' => [
            'cantidad' => [
                TipoEquipoEnum::Computadoras->value => 10,
                TipoEquipoEnum::KitsRobotica->value => 4,
            ],
            'funcionando' => [TipoEquipoEnum::Computadoras->value => 7],
            'fuera_servicio' => [TipoEquipoEnum::Computadoras->value => 3],
            'estado' => [
                TipoEquipoEnum::Computadoras->value => EstadoEquipoEnum::Regular->value,
                TipoEquipoEnum::KitsRobotica->value => EstadoEquipoEnum::Optimo->value,
            ],
            'detalle' => [TipoEquipoEnum::Computadoras->value => 'Windows 10'],
            'observaciones' => [TipoEquipoEnum::Computadoras->value => 'Dos sin memoria'],
        ],
    ]))->assertSessionHasNoErrors();

    $computadoras = Equipo::where('tipo', TipoEquipoEnum::Computadoras->value)->sole();

    expect($computadoras->cantidad)->toBe(10)
        ->and($computadoras->funcionando)->toBe(7)
        ->and($computadoras->fuera_servicio)->toBe(3)
        ->and($computadoras->estado)->toBe(EstadoEquipoEnum::Regular)
        ->and($computadoras->infocentro_id)->toBe($user->infocentro->id);
});

test('funcionando y fuera de servicio no pueden superar el total', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', fichaExtendida([
        'equipos' => [
            'cantidad' => [TipoEquipoEnum::Computadoras->value => 5],
            'funcionando' => [TipoEquipoEnum::Computadoras->value => 4],
            'fuera_servicio' => [TipoEquipoEnum::Computadoras->value => 9],
        ],
    ]))->assertSessionHasErrors('equipos.fuera_servicio.computadoras');
});

test('un equipo marcado como "no tiene" debe tener cantidad cero', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', fichaExtendida([
        'equipos' => [
            'cantidad' => [TipoEquipoEnum::TelescopiosPortatiles->value => 2],
            'estado' => [TipoEquipoEnum::TelescopiosPortatiles->value => EstadoEquipoEnum::NoTiene->value],
        ],
    ]))->assertSessionHasErrors('equipos.cantidad.telescopios_portatiles');

    $this->actingAs($user)->put('/infocentro/diagnostico', fichaExtendida([
        'equipos' => [
            'cantidad' => [TipoEquipoEnum::TelescopiosPortatiles->value => 0],
            'estado' => [TipoEquipoEnum::TelescopiosPortatiles->value => EstadoEquipoEnum::NoTiene->value],
        ],
    ]))->assertSessionHasNoErrors();
});

test('se guardan los sistemas de energia', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', fichaExtendida([
        'energias' => [
            'cantidad' => [
                TipoEnergiaEnum::PlantasElectricas->value => 2,
                TipoEnergiaEnum::PanelesSolares->value => 6,
            ],
            'estado' => [
                TipoEnergiaEnum::Corpoelectrico->value => EstadoEquipoEnum::Regular->value,
                TipoEnergiaEnum::PlantasElectricas->value => EstadoEquipoEnum::Bueno->value,
                TipoEnergiaEnum::PanelesSolares->value => EstadoEquipoEnum::Optimo->value,
            ],
        ],
    ]))->assertSessionHasNoErrors();

    $energia = Energia::where('tipo', TipoEnergiaEnum::PlantasElectricas->value)->sole();

    expect(Energia::count())->toBe(3)
        ->and($energia->cantidad)->toBe(2)
        ->and($energia->estado)->toBe(EstadoEquipoEnum::Bueno);
});

test('la conexion de corpoelectrico no guarda cantidad', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', fichaExtendida([
        'energias' => [
            'cantidad' => [TipoEnergiaEnum::Corpoelectrico->value => 3],
            'estado' => [TipoEnergiaEnum::Corpoelectrico->value => EstadoEquipoEnum::Regular->value],
        ],
    ]))->assertSessionHasNoErrors();

    expect(Energia::sole()->cantidad)->toBeNull();
});

test('el faltante de equipamiento es obligatorio si no alcanza', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', fichaExtendida([
        'suficiencia_equipamiento' => SuficienciaEquipamientoEnum::Falta->value,
    ]))->assertSessionHasErrors('equipamiento_falta');

    $this->actingAs($user)->put('/infocentro/diagnostico', fichaExtendida([
        'suficiencia_equipamiento' => SuficienciaEquipamientoEnum::Falta->value,
        'equipamiento_falta' => 'Proyector y una tablet',
    ]))->assertSessionHasNoErrors();
});

test('un equipo o energia ajeno al enum es rechazado', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', fichaExtendida([
        'equipos' => ['estado' => ['inventado' => 'optimo']],
    ]))->assertSessionHasErrors();
});
