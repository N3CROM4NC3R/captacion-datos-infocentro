<?php

use App\Enums\TipoEnergiaEnum;
use App\Enums\TipoEquipoEnum;
use Database\Factories\InfocentroUserFactory;

/**
 * El campo «detalle» de la sección G se restauró, pero solo donde el formulario
 * original lo pide. Estos tests fijan ese mapa para que no vuelva a aparecer en
 * todos los tipos por inercia.
 */
test('los equipos que piden campo libre son los del PDF', function () {
    expect(TipoEquipoEnum::Computadoras->etiquetaDetalle())->toBe('¿Con qué sistema operativo?')
        ->and(TipoEquipoEnum::KitsRobotica->etiquetaDetalle())->toBe('Tipo de kits')
        ->and(TipoEquipoEnum::Otros->etiquetaDetalle())->toBe('Especificar');
});

test('telescopios y modem no piden campo libre', function () {
    // El PDF no tiene ni una línea para ellos: solo cantidad, estado y
    // observaciones.
    expect(TipoEquipoEnum::TelescopiosPortatiles->etiquetaDetalle())->toBeNull()
        ->and(TipoEquipoEnum::ModemRouter->etiquetaDetalle())->toBeNull();
});

test('solo la fuente de energía «otra» pide campo libre', function () {
    expect(TipoEnergiaEnum::Otro->etiquetaDetalle())->toBe('Especificar')
        ->and(TipoEnergiaEnum::Corpoelectrico->etiquetaDetalle())->toBeNull()
        ->and(TipoEnergiaEnum::PlantasElectricas->etiquetaDetalle())->toBeNull()
        ->and(TipoEnergiaEnum::PanelesSolares->etiquetaDetalle())->toBeNull()
        ->and(TipoEnergiaEnum::BateriasUps->etiquetaDetalle())->toBeNull();
});

test('la ficha solo muestra el campo donde el enum lo pide', function () {
    $html = $this->actingAs(InfocentroUserFactory::new()->create())
        ->get('/infocentro/diagnostico')
        ->assertOk()
        ->getContent();

    foreach (TipoEquipoEnum::cases() as $tipo) {
        $existe = str_contains($html, "name=\"equipos[detalle][{$tipo->value}]\"");

        expect($existe)->toBe(
            $tipo->etiquetaDetalle() !== null,
            "El campo de detalle de «{$tipo->value}» no coincide con lo que pide el PDF."
        );
    }

    foreach (TipoEnergiaEnum::cases() as $tipo) {
        $existe = str_contains($html, "name=\"energias[detalle][{$tipo->value}]\"");

        expect($existe)->toBe(
            $tipo->etiquetaDetalle() !== null,
            "El campo de detalle de la energía «{$tipo->value}» no coincide con lo que pide el PDF."
        );
    }
});

test('el sistema operativo aparece antes que el formato en las computadoras', function () {
    $html = $this->actingAs(InfocentroUserFactory::new()->create())
        ->get('/infocentro/diagnostico')
        ->assertOk()
        ->getContent();

    $detalle = strpos($html, 'name="equipos[detalle][computadoras]"');
    $formato = strpos($html, 'name="equipos[formato][computadoras]"');

    expect($detalle)->not->toBeFalse()
        ->and($formato)->not->toBeFalse()
        ->and($detalle)->toBeLessThan($formato, 'El PDF lista el sistema operativo antes del formato.');
});
