<?php

use App\Enums\FrecuenciaEnum;
use App\Enums\TipoActividadEnum;
use Database\Factories\InfocentroUserFactory;

/**
 * Devuelve el HTML de la ficha con sus secciones parciales ya incluidas.
 */
function fichaParaGuardDeAlpine(object $test): string
{
    return $test->actingAs(InfocentroUserFactory::new()->create())
        ->get('/infocentro/diagnostico')
        ->assertOk()
        ->getContent();
}

/**
 * Extrae del payload el mapa de actividades, sea objeto plano o JSON.parse.
 *
 * @return array<string, bool>|null
 */
function actividadesDelPayload(string $html): ?array
{
    if (! preg_match('/actividades:\s*(.+)/', $html, $linea)) {
        return null;
    }

    $valor = trim(rtrim(trim($linea[1]), ','));

    // `@js()` de Blade compila un objeto literal a JSON.parse('…').
    if (preg_match("/^JSON\.parse\('(.*)'\)$/", $valor, $envuelto)) {
        $valor = $envuelto[1];
    }

    $desescapado = str_replace(
        ['\u0022', '\u0027', '\u0026', '\/'],
        ['"', "'", '&', '/'],
        $valor
    );

    $decodificado = json_decode($desescapado, true);

    return is_array($decodificado) ? $decodificado : null;
}

test('ningún x-show de las fichas es un literal ya evaluado', function () {
    $html = fichaParaGuardDeAlpine($this);

    // Un x-show con "true"/"false" literales llega desde el servidor ya
    // resuelto: Alpine lo aplica una vez y nunca vuelve a mirar el control que
    // debería alternarlo. Pasó en la sección F, donde frecuencia, participantes
    // y detalle solo aparecían tras guardar y recargar la página.
    preg_match_all('/<[a-z]+[^>]*\bx-show="(?:true|false)"/i', $html, $literales);

    expect($literales[0])->toBe([], 'Hay x-show estáticos: '.implode(' | ', $literales[0]));
});

test('todo contenedor x-show de las fichas lleva x-cloak', function () {
    // Se excluye la barra de navegación: sus desplegables son componentes de
    // Breeze y no forman parte de las fichas.
    $html = preg_replace('/<nav\b.*?<\/nav>/s', '', fichaParaGuardDeAlpine($this));

    preg_match_all('/<div\b[^>]*\bx-show="[^"]*"[^>]*>/i', $html, $contenedores);

    // Sin x-cloak el bloque se dibuja antes de que Alpine arranque y después
    // desaparece: un parpadeo con datos de la ficha a la vista.
    $sinCloak = array_values(array_filter(
        $contenedores[0],
        fn (string $etiqueta): bool => ! str_contains($etiqueta, 'x-cloak')
    ));

    expect($sinCloak)->toBe([], 'Contenedores x-show sin x-cloak: '.implode(' | ', $sinCloak));
});

test('cada actividad está enlazada al estado que gobierna sus campos', function () {
    $html = fichaParaGuardDeAlpine($this);

    $desenlazadas = [];

    foreach (TipoActividadEnum::cases() as $tipo) {
        if (! str_contains($html, "actividades['{$tipo->value}']")) {
            $desenlazadas[] = $tipo->value;
        }
    }

    expect($desenlazadas)->toBe([], 'Actividades sin x-model: '.implode(', ', $desenlazadas));
});

test('los campos de cada actividad quedan ocultos pero se envían', function () {
    $html = fichaParaGuardDeAlpine($this);

    // x-show oculta sin quitar del DOM: los campos se envían aunque el bloque
    // esté plegado. Si se cambiaran por x-if, dejarían de enviarse en silencio.
    expect(substr_count($html, 'name="actividades[frecuencia]['))->toBe(count(TipoActividadEnum::cases()))
        ->and(substr_count($html, 'name="actividades[participantes]['))->toBe(count(TipoActividadEnum::cases()))
        ->and(substr_count($html, 'name="actividades[detalle]['))->toBe(count(TipoActividadEnum::cases()));
});

test('el payload trae un interruptor por cada tipo de actividad', function () {
    $actividades = actividadesDelPayload(fichaParaGuardDeAlpine($this));

    expect($actividades)->toBeArray()
        ->and(array_keys($actividades))->toEqualCanonicalizing(
            array_map(fn ($t) => $t->value, TipoActividadEnum::cases())
        )
        ->and(array_sum(array_map('intval', $actividades)))->toBe(0, 'Un infocentro nuevo no debe traer actividades activas.');
});

test('las actividades guardadas llegan encendidas en el payload', function () {
    $user = InfocentroUserFactory::new()->create();

    // Vía relación: `infocentro_id` no es asignable en masa.
    $user->infocentro->actividades()->create([
        'tipo' => TipoActividadEnum::Robotica,
        'activa' => true,
        'frecuencia' => FrecuenciaEnum::Semanal,
        'participantes' => 18,
    ]);

    $actividades = actividadesDelPayload(
        $this->actingAs($user)->get('/infocentro/diagnostico')->assertOk()->getContent()
    );

    expect($actividades['robotica'])->toBeTrue()
        ->and($actividades['astronomia'])->toBeFalse();
});
