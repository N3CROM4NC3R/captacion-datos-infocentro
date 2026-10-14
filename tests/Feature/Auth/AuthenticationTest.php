<?php

use Database\Factories\AdminFactory;
use Database\Factories\InfocentroUserFactory;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('administrator can authenticate with a username', function () {
    $admin = AdminFactory::new()->create();

    $response = $this->post('/login', [
        'username' => $admin->username,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($admin);
    $response->assertRedirect(route('admin.dashboard', absolute: false));
});

test('infocentro user can authenticate with a username', function () {
    $user = InfocentroUserFactory::new()->create();

    $response = $this->post('/login', [
        'username' => $user->username,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('infocentro.diagnostico.edit', absolute: false));
});

test('users can not authenticate with an email address', function () {
    $user = InfocentroUserFactory::new()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('username');
});

test('users can not authenticate with invalid password', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->post('/login', [
        'username' => $user->username,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = InfocentroUserFactory::new()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
