<?php

use App\Support\LectorXlsx;
use Illuminate\Support\Facades\File;

function xlsxDePrueba(): string
{
    return base_path('tests/Fixtures/infocentros.xlsx');
}

test('lee la hoja usando los encabezados como nombres de columna', function () {
    $filas = (new LectorXlsx)->filas(xlsxDePrueba());

    expect($filas)->toHaveCount(3)
        ->and(array_keys($filas[0]))->toBe(['id', 'region_tipo', 'cod', 'nombre', 'estatus']);
});

test('resuelve textos en línea y textos compartidos', function () {
    $filas = (new LectorXlsx)->filas(xlsxDePrueba());

    // El nombre viene como inlineStr y el estatus desde sharedStrings.
    expect($filas[0]['nombre'])->toBe('Santa Inés')
        ->and($filas[0]['estatus'])->toBe('Abierto')
        ->and($filas[1]['estatus'])->toBe('Cerrado');
});

test('devuelve null en las celdas vacías', function () {
    $filas = (new LectorXlsx)->filas(xlsxDePrueba());

    expect($filas[2]['region_tipo'])->toBeNull()
        ->and($filas[2]['estatus'])->toBeNull()
        ->and($filas[2]['cod'])->toBe('TST03');
});

test('conserva los acentos del maestro', function () {
    $filas = (new LectorXlsx)->filas(xlsxDePrueba());

    expect($filas[0]['nombre'])->toBe('Santa Inés');
});

test('falla si el archivo no existe', function () {
    expect(fn () => (new LectorXlsx)->filas(base_path('tests/Fixtures/no-existe.xlsx')))
        ->toThrow(RuntimeException::class, 'No se encontró el archivo');
});

test('falla si al archivo no le es un xlsx', function () {
    $ruta = base_path('tests/Fixtures/no-es-un-xlsx.txt');

    File::put($ruta, 'esto no es una hoja de cálculo');

    try {
        expect(fn () => (new LectorXlsx)->filas($ruta))
            ->toThrow(RuntimeException::class, 'no es un archivo .xlsx legible');
    } finally {
        File::delete($ruta);
    }
});

test('falla si el archivo no tiene hojas', function () {
    $ruta = base_path('tests/Fixtures/sin-hojas.xlsx');

    // Cabecera PK: parece un ZIP pero no lo es.
    File::put($ruta, "PK\x03\x04");

    try {
        expect(fn () => (new LectorXlsx)->filas($ruta))
            ->toThrow(RuntimeException::class);
    } finally {
        File::delete($ruta);
    }
});
