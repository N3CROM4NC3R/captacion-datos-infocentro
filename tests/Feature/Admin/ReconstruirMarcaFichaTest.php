<?php

use App\Enums\DimensionPerfilamientoEnum;
use App\Enums\TipoEquipoEnum;
use App\Models\Estado;
use App\Models\Infocentro;
use App\Models\Municipio;
use App\Models\Parroquia;
use Illuminate\Support\Facades\DB;

/**
 * @param  array<string, mixed>  $atributos
 */
function infocentroParaReconstruir(array $atributos = []): Infocentro
{
    return Infocentro::factory()->create($atributos);
}

/**
 * Las siete puntuaciones, que es lo que hace que el perfil esté completo.
 *
 * @return array<string, string>
 */
function puntuacionesCompletas(): array
{
    return collect(DimensionPerfilamientoEnum::cases())
        ->mapWithKeys(fn ($d): array => [$d->value => '2'])
        ->all();
}

/**
 * @param  array<string, mixed>  $puntuaciones
 */
function conCascadaCompleta(Infocentro $infocentro, array $puntuaciones = []): Infocentro
{
    $estado = Estado::factory()->create();
    $municipio = Municipio::factory()->create(['estado_id' => $estado->id]);
    $parroquia = Parroquia::factory()->create(['municipio_id' => $municipio->id]);

    $infocentro->update([
        'estado_id' => $estado->id,
        'municipio_id' => $municipio->id,
        'parroquia_id' => $parroquia->id,
        'puntuaciones' => $puntuaciones === [] ? null : $puntuaciones,
    ]);

    return $infocentro->refresh();
}

test('marca la ficha que tiene el perfilamiento completo', function () {
    $infocentro = infocentroParaReconstruir(['puntuaciones' => puntuacionesCompletas()]);

    $this->artisan('fichas:reconstruir-marca --force')->assertSuccessful();

    expect($infocentro->refresh()->diagnostico_guardado_at)->not->toBeNull();
});

test('un perfil a medias no cuenta como evidencia', function () {
    // Con una sola dimensión el perfil no clasifica a nadie, así que una ficha
    // en ese estado no puede darse por hecha.
    $infocentro = infocentroParaReconstruir(['puntuaciones' => ['conectividad' => 3]]);

    $this->artisan('fichas:reconstruir-marca --force')->assertSuccessful();

    expect($infocentro->refresh()->diagnostico_guardado_at)->toBeNull();
});

test('marca la ficha que tiene filas en una sección repetible', function () {
    $infocentro = infocentroParaReconstruir();
    $infocentro->equipos()->create([
        'tipo' => TipoEquipoEnum::Computadoras,
        'cantidad' => 3,
    ]);

    $this->artisan('fichas:reconstruir-marca --force')->assertSuccessful();

    expect($infocentro->refresh()->diagnostico_guardado_at)->not->toBeNull();
});

test('marca la ficha que solo tiene la cascada territorial completa', function () {
    $infocentro = conCascadaCompleta(infocentroParaReconstruir());

    $this->artisan('fichas:reconstruir-marca --force')->assertSuccessful();

    expect($infocentro->refresh()->diagnostico_guardado_at)->not->toBeNull();
});

test('una cascada territorial a medias no cuenta como evidencia', function () {
    // El panel de administración no toca estos campos, así que estado sin
    // municipio puede venir de una ficha a medio rellenar.
    $estado = Estado::factory()->create();
    $infocentro = infocentroParaReconstruir(['estado_id' => $estado->id]);

    $this->artisan('fichas:reconstruir-marca --force')->assertSuccessful();

    expect($infocentro->refresh()->diagnostico_guardado_at)->toBeNull();
});

test('una ficha con solo la fecha de diagnóstico no cuenta como evidencia', function () {
    // Es un campo que escribe el usuario y que puede poner desde el principio;
    // no prueba que rellenara nada.
    $infocentro = infocentroParaReconstruir(['fecha_diagnostico' => '2026-01-15']);

    $this->artisan('fichas:reconstruir-marca --force')->assertSuccessful();

    expect($infocentro->refresh()->diagnostico_guardado_at)->toBeNull();
});

test('una ficha sin nada de captura no se marca', function () {
    $infocentro = infocentroParaReconstruir();

    $this->artisan('fichas:reconstruir-marca --force')->assertSuccessful();

    expect($infocentro->refresh()->diagnostico_guardado_at)->toBeNull();
});

test('la marca estampa la última actividad de la ficha, no el momento del comando', function () {
    $infocentro = infocentroParaReconstruir();

    DB::table('equipos')->insert([
        'infocentro_id' => $infocentro->id,
        'tipo' => 'computadoras',
        'cantidad' => 2,
        'funcionando' => 2,
        'fuera_servicio' => 0,
        'created_at' => '2026-03-10 10:00:00',
        'updated_at' => '2026-03-10 10:00:00',
    ]);

    $this->artisan('fichas:reconstruir-marca --force')->assertSuccessful();

    expect($infocentro->refresh()->diagnostico_guardado_at->toDateTimeString())
        ->toBe('2026-03-10 10:00:00');
});

test('la reconstrucción no pisa una marca que ya existe', function () {
    // Una ficha guardada de verdad tiene su fecha exacta; el comando solo
    // rellena huecos, no debe tocar lo que ya está.
    $real = now()->subDays(3);
    $infocentro = infocentroParaReconstruir([
        'puntuaciones' => puntuacionesCompletas(),
        'diagnostico_guardado_at' => $real,
        'diagnostico_actualizado_at' => $real,
    ]);

    $this->artisan('fichas:reconstruir-marca --force')->assertSuccessful();

    expect($infocentro->refresh()->diagnostico_guardado_at->timestamp)->toBe($real->timestamp);
});

test('la reconstrucción no mueve updated_at del infocentro', function () {
    $infocentro = infocentroParaReconstruir();
    $infocentro->equipos()->create(['tipo' => TipoEquipoEnum::Computadoras, 'cantidad' => 1]);

    $antes = $infocentro->refresh()->updated_at;

    $this->artisan('fichas:reconstruir-marca --force')->assertSuccessful();

    expect($infocentro->refresh()->updated_at->timestamp)->toBe($antes->timestamp);
});

test('la simulación no escribe nada', function () {
    $infocentro = infocentroParaReconstruir(['puntuaciones' => puntuacionesCompletas()]);

    $this->artisan('fichas:reconstruir-marca --dry-run')->assertSuccessful();

    expect($infocentro->refresh()->diagnostico_guardado_at)->toBeNull();
});

test('reconstruir dos veces no cambia nada la segunda', function () {
    $infocentro = infocentroParaReconstruir(['puntuaciones' => puntuacionesCompletas()]);

    $this->artisan('fichas:reconstruir-marca --force')->assertSuccessful();

    $primera = $infocentro->refresh()->diagnostico_guardado_at;

    $this->artisan('fichas:reconstruir-marca --force')
        ->expectsOutputToContain('no hay nada que recuperar')
        ->assertSuccessful();

    expect($infocentro->refresh()->diagnostico_guardado_at->timestamp)->toBe($primera->timestamp);
});

test('el informe desglosa cuántas fichas hay de cada tipo', function () {
    conCascadaCompleta(infocentroParaReconstruir(['puntuaciones' => puntuacionesCompletas()]));
    conCascadaCompleta(infocentroParaReconstruir());
    infocentroParaReconstruir();

    $this->artisan('fichas:reconstruir-marca --dry-run')
        ->expectsOutputToContain('con perfilamiento completo')
        ->expectsOutputToContain('solo con cascada territorial')
        ->expectsOutputToContain('quedan pendientes: 1')
        ->assertSuccessful();
});
