<?php

use App\Enums\TipoEnergiaEnum;

/**
 * Las etiquetas de G.5 están transcritas del formulario original. Se fijan aquí
 * para que un cambio de redacción no pase por alto que hay una fuente de verdad
 * externa a la que contrastar.
 */
test('las fuentes de energía se nombran como en el formulario', function () {
    expect(TipoEnergiaEnum::options())->toBe([
        'corpoelec' => 'Conexión eléctrica de Corpoelec',
        'plantas_electricas' => 'Plantas eléctricas',
        'paneles_solares' => 'Paneles solares',
        'baterias_ups' => 'Baterías / UPS',
        'otro' => 'Otro',
    ]);
});

test('la conexión eléctrica es la única fuente sin cantidad fija', function () {
    // En el PDF es la única que solo pide estado, sin cantidad.
    expect(TipoEnergiaEnum::Corpoelec->cantidadFija())->toBeFalse();

    foreach (TipoEnergiaEnum::cases() as $tipo) {
        if ($tipo !== TipoEnergiaEnum::Corpoelec) {
            expect($tipo->cantidadFija())->toBeTrue();
        }
    }
});
