<?php

use App\Enums\EstadoProyectoMincytEnum;
use App\Models\Estado;
use App\Models\VinculacionMincyt;
use Database\Factories\InfocentroUserFactory;

/**
 * H.2 usa un contador para decidir cuántos bloques de institución se muestran,
 * igual que D.2 con los facilitadores. Estos tests fijan ese comportamiento.
 */
test('H.2 ofrece un contador de instituciones con tope de 20', function () {
    $html = $this->actingAs(InfocentroUserFactory::new()->create())
        ->get('/infocentro/diagnostico')
        ->assertOk()
        ->getContent();

    expect(str_contains($html, 'H.2. Número de instituciones vinculadas'))->toBeTrue()
        ->and($html)->toContain('id="cantidad_vinculaciones"')
        ->and($html)->toContain('max="20"')
        ->and($html)->toContain('x-model.number="cantidad"');
});

test('el botón de agregar institución ya no existe', function () {
    $html = $this->actingAs(InfocentroUserFactory::new()->create())
        ->get('/infocentro/diagnostico')
        ->assertOk()
        ->getContent();

    // Con contador, subir la cantidad es lo que agrega; el botón sobraría y
    // dejaría dos formas de hacer lo mismo.
    expect(str_contains($html, 'Agregar institución'))->toBeFalse()
        ->and(str_contains($html, 'agregar()'))->toBeFalse();
});

test('eliminar institución se conserva por bloque', function () {
    $html = $this->actingAs(InfocentroUserFactory::new()->create())
        ->get('/infocentro/diagnostico')
        ->assertOk()
        ->getContent();

    expect(str_contains($html, 'Eliminar institución'))->toBeTrue()
        ->and($html)->toContain('@click="eliminar(indice)"');
});

test('las instituciones guardadas determinan el número de bloques', function () {
    $user = InfocentroUserFactory::new()->create();

    foreach (['IVIC', 'Codecal', 'Fundación Kids'] as $institucion) {
        $user->infocentro->vinculacionesMincyt()->create([
            'institucion' => $institucion,
            'estado' => EstadoProyectoMincytEnum::Activo,
        ]);
    }

    $payload = $this->actingAs($user)->get('/infocentro/diagnostico')
        ->assertOk()
        ->getContent();

    // El controller ya no rellena con filas vacías: devuelve solo lo guardado,
    // y el contador de la vista refleja ese número.
    expect($payload)->toContain('IVIC')
        ->and($payload)->toContain('Codecal')
        ->and($payload)->toContain('Fundación Kids');

    $vinculos = json_decode(
        str_replace(['\u0022', '\u0026'], ['"', '&'], extraerVinculos($payload)),
        true
    );

    expect($vinculos)->toHaveCount(3);
});

test('un infocentro sin instituciones no recibe bloques de relleno', function () {
    $user = InfocentroUserFactory::new()->create();

    $payload = $this->actingAs($user)->get('/infocentro/diagnostico')
        ->assertOk()
        ->getContent();

    expect(extraerVinculos($payload))->toBe('[]');
});

test('subir el contador a 3 y guardar conserva las tres instituciones', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->put('/infocentro/diagnostico', [
        'nombre' => 'Infocentro',
        'estado_id' => Estado::factory()->create()->id,
        'activo' => '1',
        'vinculacion_mincyt' => '1',
        'vinculaciones' => [
            'institucion' => ['IVIC', 'Codecal', 'Fundación Kids'],
            'proyecto' => ['Red', '', 'Alfabetización'],
            'estado' => ['activo', 'concluido', 'activo'],
            'ultimo_contacto' => ['2026-03-15', '', ''],
        ],
    ])->assertSessionHasNoErrors();

    expect(VinculacionMincyt::count())->toBe(3)
        ->and(VinculacionMincyt::orderBy('id')->pluck('institucion')->all())
        ->toBe(['IVIC', 'Codecal', 'Fundación Kids']);
});

test('bajar el contador descarta las instituciones sobrantes', function () {
    $user = InfocentroUserFactory::new()->create();

    foreach (['A', 'B', 'C'] as $institucion) {
        $user->infocentro->vinculacionesMincyt()->create(['institucion' => $institucion]);
    }

    // Quedan dos huecos, así que la tercera desaparece al guardar: la ficha
    // captura el estado completo, igual que en el resto de colecciones.
    $this->actingAs($user)->put('/infocentro/diagnostico', [
        'nombre' => 'Infocentro',
        'estado_id' => Estado::factory()->create()->id,
        'activo' => '1',
        'vinculacion_mincyt' => '1',
        'vinculaciones' => [
            'institucion' => ['A', 'B'],
            'proyecto' => ['', ''],
            'estado' => ['', ''],
            'ultimo_contacto' => ['', ''],
        ],
    ])->assertSessionHasNoErrors();

    expect(VinculacionMincyt::orderBy('id')->pluck('institucion')->all())->toBe(['A', 'B']);
});

/**
 * Saca el array `vinculos` del payload que publica la vista.
 */
function extraerVinculos(string $html): string
{
    if (! preg_match('/vinculos:\s*JSON\.parse\(\'(.*)\'\)/', $html, $m)) {
        return '[]';
    }

    return $m[1];
}
