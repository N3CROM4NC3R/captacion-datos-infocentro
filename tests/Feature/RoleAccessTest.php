<?php

use Database\Factories\AdminFactory;
use Database\Factories\InfocentroUserFactory;

test('administrator can reach the administration dashboard', function () {
    $admin = AdminFactory::new()->create();

    $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
});

test('administrator can reach the infocentros list', function () {
    $admin = AdminFactory::new()->create();

    $this->actingAs($admin)->get('/admin/infocentros')->assertOk();
});

test('administrator can not reach the diagnosis form', function () {
    $admin = AdminFactory::new()->create();

    $this->actingAs($admin)->get('/infocentro/diagnostico')->assertForbidden();
});

test('infocentro user can reach the diagnosis form', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->get('/infocentro/diagnostico')->assertOk();
});

test('infocentro user can not reach the administration dashboard', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->get('/admin/dashboard')->assertForbidden();
});

test('infocentro user can not manage infocentros', function () {
    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->get('/admin/infocentros')->assertForbidden();
});

test('guests are redirected to the login screen', function () {
    $this->get('/admin/dashboard')->assertRedirect('/login');
    $this->get('/infocentro/diagnostico')->assertRedirect('/login');
});

test('dashboard redirects each role to its own landing page', function () {
    $admin = AdminFactory::new()->create();

    $this->actingAs($admin)->get('/dashboard')->assertRedirect(route('admin.dashboard'));

    $user = InfocentroUserFactory::new()->create();

    $this->actingAs($user)->get('/dashboard')->assertRedirect(route('infocentro.diagnostico.edit'));
});
