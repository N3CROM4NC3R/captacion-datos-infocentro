<?php

use App\Models\Estado;
use App\Models\Municipio;
use App\Models\Parroquia;
use Database\Factories\AdminFactory;
use Database\Factories\InfocentroUserFactory;

test('el listado de municipios devuelve solo los del estado pedido', function () {
    $user = InfocentroUserFactory::new()->create();

    $estado = Estado::factory()->create(['nombre' => 'Miranda']);
    $otroEstado = Estado::factory()->create(['nombre' => 'Carabobo']);

    Municipio::factory()->create(['nombre' => 'Guaicaipuro', 'estado_id' => $estado->id]);
    Municipio::factory()->create(['nombre' => 'Paz Castillo', 'estado_id' => $estado->id]);
    Municipio::factory()->create(['nombre' => 'Valencia', 'estado_id' => $otroEstado->id]);

    $response = $this->actingAs($user)
        ->getJson("/infocentro/catalogo/municipios?estado_id={$estado->id}");

    $response->assertOk()
        ->assertJsonCount(2)
        ->assertJsonPath('0.nombre', 'Guaicaipuro')
        ->assertJsonMissing(['nombre' => 'Valencia']);
});

test('el listado de parroquias devuelve solo las del municipio pedido', function () {
    $user = InfocentroUserFactory::new()->create();

    $municipio = Municipio::factory()->create();
    $otroMunicipio = Municipio::factory()->create();

    Parroquia::factory()->create(['nombre' => 'Cúa', 'municipio_id' => $municipio->id]);
    Parroquia::factory()->create(['nombre' => 'Macanao', 'municipio_id' => $otroMunicipio->id]);

    $response = $this->actingAs($user)
        ->getJson("/infocentro/catalogo/parroquias?municipio_id={$municipio->id}");

    $response->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.nombre', 'Cúa');
});

test('sin identificador el listado devuelve una colección vacía', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->getJson('/infocentro/catalogo/municipios')
        ->assertOk()
        ->assertExactJson([]);

    $this->actingAs($user)->getJson('/infocentro/catalogo/parroquias')
        ->assertOk()
        ->assertExactJson([]);
});

test('el catálogo no está disponible para invitados ni para el administrador', function () {
    $this->getJson('/infocentro/catalogo/municipios')->assertUnauthorized();

    $admin = AdminFactory::new()->create();

    $this->actingAs($admin)->getJson('/infocentro/catalogo/municipios')->assertForbidden();
});
