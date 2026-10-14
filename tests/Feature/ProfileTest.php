<?php

use App\Enums\RoleEnum;
use Database\Factories\AdminFactory;
use Database\Factories\InfocentroFactory;

test('profile page is displayed', function () {
    $user = AdminFactory::new()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = AdminFactory::new()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'username' => 'test.user',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test.user', $user->username);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = AdminFactory::new()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'username' => $user->username,
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('usernames must be unique across users', function () {
    $user = AdminFactory::new()->create();
    AdminFactory::new()->create(['username' => 'ocupado']);

    $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => $user->name,
            'username' => 'ocupado',
            'email' => $user->email,
        ])
        ->assertSessionHasErrors('username');
});

test('the infocentro of the current user can not be reassigned through the profile', function () {
    $user = AdminFactory::new()->create(['infocentro_id' => null]);
    $otroInfocentro = InfocentroFactory::new()->create();

    $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'infocentro_id' => $otroInfocentro->id,
        ])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->infocentro_id)->toBeNull();
});

test('the role of the current user can not be changed through the profile', function () {
    $user = AdminFactory::new()->create();

    $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'role' => 'infocentro',
        ])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->hasRole(RoleEnum::Infocentro))->toBeFalse();
});

test('user can delete their account', function () {
    $user = AdminFactory::new()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('correct password must be provided to delete account', function () {
    $user = AdminFactory::new()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});
