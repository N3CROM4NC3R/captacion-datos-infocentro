<?php

use App\Models\Estado;
use App\Models\Municipio;
use App\Models\Parroquia;
use Database\Factories\InfocentroUserFactory;
use Database\Seeders\TerritorioSeeder;

test('el seeder carga el catálogo territorial completo', function () {
    $this->seed(TerritorioSeeder::class);

    expect(Estado::count())->toBe(24)
        ->and(Municipio::count())->toBe(335)
        ->and(Parroquia::count())->toBe(1138);
});

test('el catálogo no incluye Dependencias Federales', function () {
    $this->seed(TerritorioSeeder::class);

    expect(Estado::where('nombre', 'Dependencias Federales')->exists())->toBeFalse();
});

test('cada estado tiene al menos un municipio', function () {
    $this->seed(TerritorioSeeder::class);

    expect(Estado::doesntHave('municipios')->count())->toBe(0);
});

test('el dataset deja dos municipios sin parroquias', function () {
    $this->seed(TerritorioSeeder::class);

    // Hueco conocido del dataset de origen, no de la conversión. Si algún día
    // se corrige en la fuente, este test obliga a revisarlo.
    $sinParroquias = Municipio::doesntHave('parroquias')->pluck('nombre')->sort()->values()->all();

    expect($sinParroquias)->toBe(['Agua Blanca', 'Bolívar']);
});

test('el seeder es idempotente', function () {
    $this->seed(TerritorioSeeder::class);

    $estados = Estado::count();
    $municipios = Municipio::count();
    $parroquias = Parroquia::count();

    $this->seed(TerritorioSeeder::class);

    expect(Estado::count())->toBe($estados)
        ->and(Municipio::count())->toBe($municipios)
        ->and(Parroquia::count())->toBe($parroquias);
});

test('el catálogo respects la cadena de dependencia geografica', function () {
    $this->seed(TerritorioSeeder::class);

    expect(Parroquia::whereDoesntHave('municipio')->count())->toBe(0)
        ->and(Municipio::whereDoesntHave('estado')->count())->toBe(0);
});

test('la cascada del catalogo filtra con los datos reales', function () {
    $this->seed(TerritorioSeeder::class);

    $user = InfocentroUserFactory::new()->create();

    $estado = Estado::where('nombre', 'Táchira')->sole();
    $municipio = Municipio::where('estado_id', $estado->id)->where('nombre', 'San Cristóbal')->sole();
    $parroquia = Parroquia::where('municipio_id', $municipio->id)->firstOrFail();

    $this->actingAs($user)
        ->getJson("/infocentro/catalogo/municipios?estado_id={$estado->id}")
        ->assertOk()
        ->assertJsonFragment(['id' => $municipio->id, 'nombre' => 'San Cristóbal'])
        ->assertJsonMissing(['nombre' => 'Guaicaipuro']);

    $this->actingAs($user)
        ->getJson("/infocentro/catalogo/parroquias?municipio_id={$municipio->id}")
        ->assertOk()
        ->assertJsonFragment(['id' => $parroquia->id, 'nombre' => $parroquia->nombre]);
});

test('el formulario ofrece los estados reales como opciones', function () {
    $this->seed(TerritorioSeeder::class);

    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->get('/infocentro/diagnostico')
        ->assertOk()
        ->assertSee('Táchira')
        ->assertSee('Zulia')
        ->assertSee('Distrito Capital');
});
