<?php

test('public registration is not available', function () {
    $this->get('/register')->assertNotFound();
});

test('accounts can not be created through the public registration endpoint', function () {
    $this->post('/register', [
        'name' => 'Intruso',
        'username' => 'intruso',
        'email' => 'intruso@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();
});
