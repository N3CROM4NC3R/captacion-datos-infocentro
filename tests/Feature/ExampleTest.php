<?php

use Database\Factories\AdminFactory;

it('sends a guest from the root to the login screen', function () {
    $this->get('/')->assertRedirect(route('login'));
});

it('does not loop when an authenticated user enters from the root', function () {
    $admin = AdminFactory::new()->create();

    // Con la raíz apuntando al login, hace falta fijar que `guest` no devuelve
    // al usuario a `/`: eso sería un bucle de redirección. La segunda parada
    // tiene que ser el dashboard, que ya reparte por rol.
    $this->actingAs($admin)->get('/')->assertRedirect(route('login'));

    $this->actingAs($admin)->get('/login')->assertRedirect(route('dashboard'));
});
