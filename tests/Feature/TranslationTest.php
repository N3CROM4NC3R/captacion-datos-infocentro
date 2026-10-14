<?php

use App\Enums\OperatividadEnum;
use App\Enums\SuficienciaHorarioEnum;
use Database\Factories\InfocentroUserFactory;

test('los mensajes de validación se muestran en español', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', [
        'nombre' => '',
        'operatividad' => OperatividadEnum::NoOperativo->value,
        'horario_suficiente' => SuficienciaHorarioEnum::No->value,
    ])->assertSessionHasErrors([
        'nombre' => 'El campo nombre es obligatorio.',
        'operatividad_detalle' => 'Especifica la limitación o la causa por la que no está operativo.',
        'horario_suficiente_motivo' => 'Explica por qué el horario no responde a las necesidades de la comunidad.',
    ]);
});

test('los mensajes de validación repiten el nombre del campo en español', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', [
        'nombre' => 'Infocentro',
        'horario_inicio' => 'no es una hora',
    ])->assertSessionHasErrors([
        'horario_inicio' => 'El campo horario de inicio no coincide con el formato H:i.',
    ]);
});

test('los mensajes de validación de la ficha usan el nombre de sección', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', [
        'nombre' => 'Infocentro',
        'latitud' => '500',
    ])->assertSessionHasErrors([
        'latitud' => 'El campo latitud debe ser un valor entre -90 y 90.',
    ]);
});

test('las pantallas de autenticación se muestran traducidas', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('Usuario')
        ->assertSee('Contraseña')
        ->assertSee('Iniciar sesión')
        ->assertSee('¿Olvidaste tu contraseña?');

    $this->get('/forgot-password')
        ->assertOk()
        ->assertSee('Enviar enlace de restablecimiento');
});
