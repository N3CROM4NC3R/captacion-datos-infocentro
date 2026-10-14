<?php

use Database\Factories\AdminFactory;

/**
 * Devuelve el HTML de una pantalla que usa cada layout.
 *
 * El logo vive en la barra de navegación del layout autenticado y en el
 * encabezado del layout invitado, y el favicon se declara en el `<head>` de
 * ambos. Los dos tienen que quedar cubiertos, así que el recorrido va por una
 * pantalla de cada uno.
 *
 * @return array<string, string>
 */
function pantallasDeCadaLayout(object $test): array
{
    // `actingAs` queda activo en el test para las peticiones siguientes, así que
    // la pantalla de invitados se pide antes: en `/login` el middleware `guest`
    // manda a `/dashboard` a quien ya está autenticado.
    $invitado = $test->get('/login')->assertOk()->getContent();

    $autenticado = $test->actingAs(AdminFactory::new()->create())
        ->get('/admin/dashboard')
        ->assertOk()
        ->getContent();

    return [
        'guest' => $invitado,
        'app' => $autenticado,
    ];
}

test('las pantallas muestran el logo del proyecto', function () {
    foreach (pantallasDeCadaLayout($this) as $layout => $html) {
        expect(str_contains($html, '<img src="'.asset('logo.png').'"'))
            ->toBeTrue("El layout «{$layout}» no muestra el logo.");
    }
});

test('las pantallas declaran el favicon del proyecto', function () {
    foreach (pantallasDeCadaLayout($this) as $layout => $html) {
        expect(str_contains($html, '<link rel="icon" href="'.asset('favicon.ico').'"'))
            ->toBeTrue("El layout «{$layout}» no declara el favicon.");
    }
});

test('el archivo del logo y el del favicon existen y no están vacíos', function (string $archivo) {
    // Un favicon vacío no lo delata ningún marcado: el navegador acepta la
    // respuesta y dibuja un icono roto. El tamaño es lo único que lo denuncia.
    $ruta = public_path($archivo);

    expect($ruta)->toBeFile()->and(filesize($ruta))->toBeGreaterThan(0);
})->with(['logo.png', 'favicon.ico']);
