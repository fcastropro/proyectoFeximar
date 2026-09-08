<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('admin.dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('farm users are redirected to farm dashboard after login', function () {
    $user = User::factory()->create([
        'email' => 'farm-login@test.local',
        'password' => bcrypt('password'),
    ]);

    $farm = \App\Models\Farm::query()->create([
        'name' => 'Login Farm',
        'active' => true,
    ]);

    \App\Models\FarmUser::query()->create([
        'farm_id' => $farm->id,
        'user_id' => $user->id,
        'role' => 'manager',
        'active' => true,
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('farm.dashboard', absolute: false));
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('farm user logout destroys session and redirects to home', function () {
    $user = User::factory()->create([
        'email' => 'farm-logout@test.local',
    ]);

    $farm = \App\Models\Farm::query()->create([
        'name' => 'Logout Farm',
        'active' => true,
    ]);

    \App\Models\FarmUser::query()->create([
        'farm_id' => $farm->id,
        'user_id' => $user->id,
        'role' => 'manager',
        'active' => true,
    ]);

    $this->actingAs($user)
        ->post('/logout')
        ->assertRedirect('/');

    $this->assertGuest();

    $this->get('/')
        ->assertOk()
        ->assertSee('INICIAR SESIÓN', false)
        ->assertDontSee('MI PANEL', false)
        ->assertDontSee('Logout Farm', false);
});

test('admin user logout destroys session and redirects to home', function () {
    $admin = User::factory()->create([
        'name' => 'Admin Logout Test',
        'email' => 'admin-logout@test.local',
    ]);

    $this->actingAs($admin)
        ->post('/logout')
        ->assertRedirect('/');

    $this->assertGuest();

    $this->get('/')
        ->assertOk()
        ->assertSee('INICIAR SESIÓN', false)
        ->assertDontSee('Admin Logout Test', false)
        ->assertDontSee('PANEL ADMIN', false);
});
