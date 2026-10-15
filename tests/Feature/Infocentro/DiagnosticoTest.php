<?php

use App\Enums\CondicionEspacioEnum;
use App\Enums\InstalacionEnum;
use App\Enums\NivelAcademicoEnum;
use App\Enums\OperatividadEnum;
use App\Enums\ProveedorInternetEnum;
use App\Enums\ServicioInternetEnum;
use App\Enums\SuficienciaHorarioEnum;
use App\Enums\TipoServicioCantvEnum;
use App\Models\Estado;
use App\Models\Facilitador;
use App\Models\Municipio;
use App\Models\Parroquia;
use Database\Factories\AdminFactory;
use Database\Factories\InfocentroUserFactory;

/**
 * Build a valid base payload, overridable per test.
 *
 * @param  array<string, mixed>  $campos
 */
function ficha(array $campos = []): array
{
    $estado = Estado::factory()->create();
    $municipio = Municipio::factory()->create(['estado_id' => $estado->id]);
    $parroquia = Parroquia::factory()->create(['municipio_id' => $municipio->id]);

    return array_merge([
        'nombre' => 'Infocentro Centro',
        'codigo' => 'INF-001',
        'estado_id' => $estado->id,
        'municipio_id' => $municipio->id,
        'parroquia_id' => $parroquia->id,
        'activo' => '1',
        'tiene_facilitador_titular' => '0',
    ], $campos);
}

/**
 * Build the parallel-array payload the repeatable facilitator block posts.
 *
 * @param  array<int, array<string, mixed>>  $facilitadores
 * @return array<string, mixed>
 */
function conFacilitadores(array $facilitadores): array
{
    $payload = ficha();

    $payload['facilitadores'] = [];

    foreach (['nombre', 'nivel_academico', 'titulo_carrera', 'anios_experiencia', 'formacion_tic', 'formacion_tic_detalle', 'formacion_ciencias', 'formacion_ciencias_area', 'horas_semanales'] as $campo) {
        $payload['facilitadores'][$campo] = array_column($facilitadores, $campo);
    }

    return $payload;
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function facilitador(array $overrides = []): array
{
    return [
        'nombre' => 'Ana Pérez',
        'nivel_academico' => NivelAcademicoEnum::Universitario->value,
        'titulo_carrera' => 'Ingeniería en Computación',
        'anios_experiencia' => 5,
        'formacion_tic' => '1',
        'formacion_tic_detalle' => 'Curso de fundamentals TIC',
        'formacion_ciencias' => '0',
        'formacion_ciencias_area' => null,
        'horas_semanales' => 20,
        ...$overrides,
    ];
}

test('la ficha se muestra con las cuatro secciones', function () {
    $user = InfocentroUserFactory::new()->create();

    $response = $this->actingAs($user)->get('/infocentro/diagnostico');

    $response->assertOk();
    $response->assertSee(__('Sección A. Identificación del infocentro'));
    $response->assertSee(__('Sección B. Conectividad'));
    $response->assertSee(__('Sección C. Operatividad'));
    $response->assertSee(__('Sección D. Facilitadores'));
});

test('la caja de identidad muestra código, nombre y estatus', function () {
    $user = InfocentroUserFactory::new()->create();
    $infocentro = $user->infocentro;
    $infocentro->update(['nombre' => 'Infocentro de la Iglesia']);

    $html = $this->actingAs($user)->get('/infocentro/diagnostico')
        ->assertOk()
        ->getContent();

    // Los tres bloques viven en el mismo `dl` de identidad.
    expect(str_contains($html, $infocentro->codigo))->toBeTrue()
        ->and(str_contains($html, 'Infocentro de la Iglesia'))->toBeTrue()
        ->and(str_contains($html, '<label for="activo"'))->toBeTrue(
            'El estatus necesita su etiqueta asociada al select para que el nombre del campo sea accesible.',
        )
        ->and(str_contains($html, 'name="activo"'))->toBeTrue()
        ->and(str_contains($html, '>'.__('Activo').'</option>'))->toBeTrue();
});

test('la caja de identidad se reparte con un grid adaptable', function () {
    $user = InfocentroUserFactory::new()->create();

    $html = $this->actingAs($user)->get('/infocentro/diagnostico')
        ->assertOk()
        ->getContent();

    preg_match('/<dl class="([^"]*grid[^"]*)">/', $html, $caja);

    $clases = $caja[1] ?? '';

    // Un `flex` de anchos fijos no refluye: en móvil solo apretaba el
    // desplegable de estatus hasta dejarlo ilegible. El grid devuelve el
    // control a una columna por debajo de `sm` y reparte el resto con
    // `minmax(0,1fr)`, que es lo que deja envolver un nombre largo en vez de
    // ensanchar la fila.
    expect($clases)->not->toBe('')
        ->and(str_contains($clases, 'grid'))->toBeTrue('La caja de identidad debe usar grid.')
        ->and(str_contains($clases, 'flex'))->toBeFalse('Sin flex: no hay reflujo a columna.')
        ->and(str_contains($clases, 'sm:grid-cols-'))->toBeTrue(
            'Debe declarar sus columnas a partir de sm.',
        )
        ->and(str_contains($clases, 'minmax(0,1fr)'))->toBeTrue(
            'La columna del nombre necesita minmax(0,1fr) para que el texto largo envuelva.',
        );

    // Los tres bloques comparten altura: si no, el select descuadra la fila.
    expect(substr_count($html, 'min-h-11'))->toBe(3);
});

test('el nombre largo no rompe la caja de identidad', function () {
    $user = InfocentroUserFactory::new()->create();
    $user->infocentro->update([
        'nombre' => 'Infocentro Comunitario de la Parroquia San Juan de los Morros del Municipio Miranda',
    ]);

    // Que el texto llegue entero a la vista. Que no desborde el ancho no se
    // puede comprobar sin un motor de layout, así que lo que se fija aquí es
    // que el nombre ni se trunca ni se inyecta.
    $this->actingAs($user)->get('/infocentro/diagnostico')
        ->assertOk()
        ->assertSee('Infocentro Comunitario de la Parroquia San Juan de los Morros del Municipio Miranda');
});

test('el administrador no puede ver la ficha', function () {
    $admin = AdminFactory::new()->create();

    $this->actingAs($admin)->get('/infocentro/diagnostico')->assertForbidden();
});

test('un invitado es redirigido al login', function () {
    $this->get('/infocentro/diagnostico')->assertRedirect('/login');
    $this->put('/infocentro/diagnostico', [])->assertRedirect('/login');
});

test('el nombre, la direccion y el telefono se guardan desde la ficha', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', ficha([
        'nombre' => 'Infocentro Renombrado',
        'direccion' => 'Av. Bolívar 123',
        'telefono' => '04121234567',
    ]))->assertSessionHasNoErrors();

    $infocentro = $user->infocentro->refresh();

    expect($infocentro->nombre)->toBe('Infocentro Renombrado')
        ->and($infocentro->direccion)->toBe('Av. Bolívar 123')
        ->and($infocentro->telefono)->toBe('04121234567');
});

test('el nombre del infocentro es obligatorio', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', ficha([
        'nombre' => '',
    ]))->assertSessionHasErrors('nombre');
});

test('se guardan los datos de identificación', function () {
    $user = InfocentroUserFactory::new()->create();

    $response = $this->actingAs($user)->put('/infocentro/diagnostico', ficha([
        'comuna_circuito' => 'Comuna Norte',
        'consejo_comunal' => 'Consejo Comunal Norte',
        'sala_autogobierno_comunal' => 'SAC Norte',
        'latitud' => '10.6420000',
        'longitud' => '-71.5520000',
        'fecha_diagnostico' => '2026-10-14',
        'nombre_diagnosticador' => 'María Pérez',
        'cargo_institucion' => 'Facilitadora',
    ]));

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('infocentro.diagnostico.edit'));

    $infocentro = $user->infocentro->refresh();

    expect($infocentro->nombre)->toBe('Infocentro Centro')
        ->and($infocentro->comuna_circuito)->toBe('Comuna Norte')
        ->and($infocentro->consejo_comunal)->toBe('Consejo Comunal Norte')
        ->and($infocentro->sala_autogobierno_comunal)->toBe('SAC Norte')
        ->and($infocentro->latitud)->toBe('10.6420000')
        ->and($infocentro->longitud)->toBe('-71.5520000')
        ->and($infocentro->fecha_diagnostico->format('Y-m-d'))->toBe('2026-10-14')
        ->and($infocentro->nombre_diagnosticador)->toBe('María Pérez')
        ->and($infocentro->cargo_institucion)->toBe('Facilitadora');
});

test('el código se muestra pero no se edita desde la ficha', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->get('/infocentro/diagnostico')
        ->assertOk()
        ->assertSee('INF-', false);

    $this->actingAs($user)->put('/infocentro/diagnostico', ficha([
        'codigo' => 'HACKED',
    ]))->assertSessionHasNoErrors();

    expect($user->infocentro->refresh()->codigo)->not->toBe('HACKED');
});

test('el estatus y el teléfono de contacto se editan desde la ficha', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', ficha([
        'telefono' => '04121234567',
        'activo' => '0',
        'razon_cierre' => 'Incendio',
    ]))->assertSessionHasNoErrors();

    $infocentro = $user->infocentro->refresh();

    expect($infocentro->telefono)->toBe('04121234567')
        ->and($infocentro->activo)->toBeFalse()
        ->and($infocentro->razon_cierre)->toBe('Incendio');
});

test('marcar el infocentro inactivo exige indicar la razón del cierre', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', ficha([
        'activo' => '0',
        'razon_cierre' => '',
    ]))->assertSessionHasErrors([
        'razon_cierre' => 'Indica por qué el infocentro está inactivo.',
    ]);
});

test('reactivar el infocentro deja la razón de cierre sin valor', function () {
    $user = InfocentroUserFactory::new()->create();
    $user->infocentro->update(['activo' => false, 'razon_cierre' => 'Incendio']);

    $this->actingAs($user)->put('/infocentro/diagnostico', ficha([
        'activo' => '1',
    ]))->assertSessionHasNoErrors();

    expect($user->infocentro->refresh())
        ->activo->toBeTrue()
        ->razon_cierre->toBeNull();
});

test('el municipio debe pertenecer al estado seleccionado', function () {
    $user = InfocentroUserFactory::new()->create();
    $base = ficha();
    $ajeno = Municipio::factory()->create(['estado_id' => Estado::factory()->create()->id]);

    $this->actingAs($user)->put('/infocentro/diagnostico', [
        ...$base,
        'municipio_id' => $ajeno->id,
    ])->assertSessionHasErrors('municipio_id');
});

test('la parroquia debe pertenecer al municipio seleccionado', function () {
    $user = InfocentroUserFactory::new()->create();
    $base = ficha();
    $ajena = Parroquia::factory()->create(['municipio_id' => Municipio::factory()->create()->id]);

    $this->actingAs($user)->put('/infocentro/diagnostico', [
        ...$base,
        'parroquia_id' => $ajena->id,
    ])->assertSessionHasErrors('parroquia_id');
});

test('la latitud y la longitud se validan en su rango', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', ficha([
        'latitud' => '120',
        'longitud' => '400',
    ]))->assertSessionHasErrors(['latitud', 'longitud']);
});

test('se guardan la conectividad como enums', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', ficha([
        'servicio_internet' => ServicioInternetEnum::SiIntermitente->value,
        'proveedor_internet' => ProveedorInternetEnum::Cantv->value,
        'proveedor_detalle' => TipoServicioCantvEnum::FibraOptica->value,
        'observaciones_conectividad' => 'Se corta en las tardes',
    ]))->assertSessionHasNoErrors();

    $infocentro = $user->infocentro->refresh();

    expect($infocentro->servicio_internet)->toBe(ServicioInternetEnum::SiIntermitente)
        ->and($infocentro->proveedor_internet)->toBe(ProveedorInternetEnum::Cantv)
        ->and($infocentro->proveedor_detalle)->toBe(TipoServicioCantvEnum::FibraOptica)
        ->and($infocentro->observaciones_conectividad)->toBe('Se corta en las tardes');
});

test('el nombre del proveedor es obligatorio para empresa privada', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', ficha([
        'proveedor_internet' => ProveedorInternetEnum::EmpresaPrivada->value,
    ]))->assertSessionHasErrors('proveedor_nombre');

    $this->actingAs($user)->put('/infocentro/diagnostico', ficha([
        'proveedor_internet' => ProveedorInternetEnum::EmpresaPrivada->value,
        'proveedor_nombre' => 'Cable Andino',
    ]))->assertSessionHasNoErrors();
});

test('el nombre del proveedor no se exige para CANTV', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', ficha([
        'proveedor_internet' => ProveedorInternetEnum::Cantv->value,
        'proveedor_detalle' => TipoServicioCantvEnum::Aba->value,
    ]))->assertSessionHasNoErrors();
});

test('el tipo de servicio de CANTV es obligatorio', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', ficha([
        'proveedor_internet' => ProveedorInternetEnum::Cantv->value,
    ]))->assertSessionHasErrors([
        'proveedor_detalle' => 'Indica el tipo de servicio de CANTV: fibra óptica, ABA o satelital.',
    ]);
});

test('el tipo de servicio no se exige con proveedores que no son CANTV', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', ficha([
        'proveedor_internet' => ProveedorInternetEnum::Movilnet->value,
    ]))->assertSessionHasNoErrors();

    expect($user->infocentro->refresh()->proveedor_detalle)->toBeNull();
});

test('un tipo de servicio fuera del enum es rechazado', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', ficha([
        'proveedor_internet' => ProveedorInternetEnum::Cantv->value,
        'proveedor_detalle' => 'Fibra óptica',
    ]))->assertSessionHasErrors('proveedor_detalle');
});

test('un valor fuera del enum es rechazado', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', ficha([
        'servicio_internet' => 'invalido',
    ]))->assertSessionHasErrors('servicio_internet');
});

test('el detalle es obligatorio si el infocentro opera con limitaciones', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', ficha([
        'operatividad' => OperatividadEnum::ConLimitaciones->value,
    ]))->assertSessionHasErrors('operatividad_detalle');

    $this->actingAs($user)->put('/infocentro/diagnostico', ficha([
        'operatividad' => OperatividadEnum::ConLimitaciones->value,
        'operatividad_detalle' => 'Solo 2 equipos',
    ]))->assertSessionHasNoErrors();

    expect($user->infocentro->refresh()->operatividad)->toBe(OperatividadEnum::ConLimitaciones);
});

test('el motivo del horario es obligatorio si no responde a las necesidades', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', ficha([
        'horario_suficiente' => SuficienciaHorarioEnum::Parcialmente->value,
    ]))->assertSessionHasErrors('horario_suficiente_motivo');

    $this->actingAs($user)->put('/infocentro/diagnostico', ficha([
        'horario_suficiente' => SuficienciaHorarioEnum::Si->value,
    ]))->assertSessionHasNoErrors();
});

test('se guardan los dias de atencion y el horario', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', ficha([
        'dias_atencion' => ['lunes', 'martes', 'miercoles'],
        'horario_inicio' => '08:00',
        'horario_fin' => '17:00',
        'condicion_espacio' => CondicionEspacioEnum::MantenimientoMenor->value,
    ]))->assertSessionHasNoErrors();

    $infocentro = $user->infocentro->refresh();

    expect($infocentro->dias_atencion)->toBe(['lunes', 'martes', 'miercoles'])
        ->and($infocentro->dias())->toHaveCount(3)
        ->and($infocentro->horario_inicio?->format('H:i'))->toBe('08:00')
        ->and($infocentro->condicion_espacio)->toBe(CondicionEspacioEnum::MantenimientoMenor);
});

test('el domingo es un dia valido', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', ficha([
        'dias_atencion' => ['domingo'],
    ]))->assertSessionHasNoErrors();
});

test('un dia fuera del enum es rechazado', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', ficha([
        'dias_atencion' => ['lunes', 'fundicion'],
    ]))->assertSessionHasErrors('dias_atencion.1');
});

test('el horario de fin debe ser posterior al de inicio', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', ficha([
        'horario_inicio' => '17:00',
        'horario_fin' => '08:00',
    ]))->assertSessionHasErrors('horario_fin');
});

test('se guardan las instalaciones marcadas', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', ficha([
        'instalaciones' => [
            InstalacionEnum::TechoBueno->value,
            InstalacionEnum::BañosFueraServicio->value,
        ],
    ]))->assertSessionHasNoErrors();

    expect($user->infocentro->refresh()->instalaciones)->toBe([
        InstalacionEnum::TechoBueno->value,
        InstalacionEnum::BañosFueraServicio->value,
    ]);
});

test('una instalacion fuera del enum es rechazada', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', ficha([
        'instalaciones' => ['piscina'],
    ]))->assertSessionHasErrors('instalaciones.0');
});

test('se guarda el indicador de facilitador titular', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', ficha([
        'tiene_facilitador_titular' => '1',
    ]))->assertSessionHasNoErrors();

    expect($user->infocentro->refresh()->tiene_facilitador_titular)->toBeTrue();
});

test('se guardan los facilitadores enviados', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', conFacilitadores([
        facilitador(),
        facilitador([
            'nombre' => 'Luis Gómez',
            'nivel_academico' => NivelAcademicoEnum::Tsu->value,
            'anios_experiencia' => 2,
            'horas_semanales' => 10,
        ]),
    ]))->assertSessionHasNoErrors();

    $facilitadores = Facilitador::orderBy('nombre')->get();

    expect($facilitadores)->toHaveCount(2)
        ->and($facilitadores[0]->nombre)->toBe('Ana Pérez')
        ->and($facilitadores[0]->nivel_academico)->toBe(NivelAcademicoEnum::Universitario)
        ->and($facilitadores[0]->titulo_carrera)->toBe('Ingeniería en Computación')
        ->and($facilitadores[0]->anios_experiencia)->toBe(5)
        ->and($facilitadores[0]->formacion_tic)->toBeTrue()
        ->and($facilitadores[0]->formacion_ciencias)->toBeFalse()
        ->and($facilitadores[1]->nombre)->toBe('Luis Gómez')
        ->and($facilitadores[1]->nivel_academico)->toBe(NivelAcademicoEnum::Tsu)
        ->and($facilitadores[1]->infocentro_id)->toBe($user->infocentro->id);
});

test('el detalle de formacion es obligatorio cuando se responde que si', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', conFacilitadores([
        facilitador(['formacion_tic' => '1', 'formacion_tic_detalle' => null]),
    ]))->assertSessionHasErrors('facilitadores.formacion_tic_detalle.0');

    $this->actingAs($user)->put('/infocentro/diagnostico', conFacilitadores([
        facilitador(['formacion_ciencias' => '1', 'formacion_ciencias_area' => null]),
    ]))->assertSessionHasErrors('facilitadores.formacion_ciencias_area.0');
});

test('el detalle de formacion no se exige cuando se responde que no', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', conFacilitadores([
        facilitador([
            'formacion_tic' => '0',
            'formacion_tic_detalle' => null,
            'formacion_ciencias' => '0',
            'formacion_ciencias_area' => null,
        ]),
    ]))->assertSessionHasNoErrors();
});

test('un nivel academico fuera del enum es rechazado', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', conFacilitadores([
        facilitador(['nivel_academico' => 'maestria']),
    ]))->assertSessionHasErrors('facilitadores.nivel_academico.0');
});

test('los facilitadores se reemplazan al volver a guardar', function () {
    $user = InfocentroUserFactory::new()->create();
    Facilitador::factory()->for($user->infocentro)->create(['nombre' => 'Obsoleto']);

    $this->actingAs($user)->put('/infocentro/diagnostico', conFacilitadores([
        facilitador(['nombre' => 'Vigente']),
    ]))->assertSessionHasNoErrors();

    expect(Facilitador::where('nombre', 'Obsoleto')->exists())->toBeFalse()
        ->and(Facilitador::where('nombre', 'Vigente')->exists())->toBeTrue();
});

test('enviar la ficha sin facilitadores los elimina a todos', function () {
    $user = InfocentroUserFactory::new()->create();
    Facilitador::factory()->for($user->infocentro)->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', ficha())->assertSessionHasNoErrors();

    expect(Facilitador::where('infocentro_id', $user->infocentro->id)->count())->toBe(0);
});

test('un usuario de infocentro no puede modificar el de otro', function () {
    $user = InfocentroUserFactory::new()->create();
    $otro = InfocentroUserFactory::new()->create();

    $nombreOriginal = $otro->infocentro->nombre;
    $codigoOriginal = $otro->infocentro->codigo;

    // El controlador opera siempre sobre el infocentro del usuario conectado,
    // así que este cambia el suyo propio y no puede alcanzar el del otro.
    $this->actingAs($user)->put('/infocentro/diagnostico', ficha([
        'nombre' => 'Mi propio nombre',
        'codigo' => $otro->infocentro->codigo,
    ]))->assertSessionHasNoErrors();

    expect($user->infocentro->refresh()->nombre)->toBe('Mi propio nombre')
        ->and($user->infocentro->codigo)->not->toBe($codigoOriginal)
        ->and($otro->infocentro->refresh()->nombre)->toBe($nombreOriginal)
        ->and($otro->infocentro->codigo)->toBe($codigoOriginal);
});

test('la ficha muestra los datos ya guardados', function () {
    $user = InfocentroUserFactory::new()->create();
    $estado = Estado::factory()->create(['nombre' => 'Zulia']);

    $user->infocentro->update([
        'estado_id' => $estado->id,
        'comuna_circuito' => 'Comuna Sur',
        'servicio_internet' => ServicioInternetEnum::No,
    ]);

    Facilitador::factory()->for($user->infocentro)->create(['nombre' => 'Facilitador Guardado']);

    $this->actingAs($user)->get('/infocentro/diagnostico')
        ->assertOk()
        ->assertSee('Comuna Sur')
        ->assertSee('Facilitador Guardado')
        ->assertSee('Zulia');
});

test('un error de validacion conserva lo que ya habia', function () {
    $user = InfocentroUserFactory::new()->create();
    Facilitador::factory()->for($user->infocentro)->create(['nombre' => 'Facilitador Guardado']);

    $this->actingAs($user)->put('/infocentro/diagnostico', ficha(['nombre' => '']))
        ->assertSessionHasErrors('nombre');

    $this->actingAs($user)->get('/infocentro/diagnostico')
        ->assertOk()
        ->assertSee('Facilitador Guardado');
});
