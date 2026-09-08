<?php

use App\Models\Farm;
use App\Models\FarmUser;
use App\Models\User;

test('guest can view home', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('INICIAR SESIÓN', false)
        ->assertSee('signup-popup', false)
        ->assertDontSee('MI PANEL', false)
        ->assertDontSee('PANEL ADMIN', false)
        ->assertDontSee('CATÁLOGO 2026', false);
});

test('authenticated admin sees name and admin panel link on home', function () {
    $admin = User::factory()->create(['name' => 'Administrador FEXIMAR']);

    $this->actingAs($admin)
        ->get('/')
        ->assertOk()
        ->assertSee('Administrador FEXIMAR', false)
        ->assertSee('FEXIMAR', false)
        ->assertSee('PANEL ADMIN', false)
        ->assertDontSee('INICIAR SESIÓN', false)
        ->assertDontSee('CATÁLOGO 2026', false)
        ->assertSee(route('admin.dashboard', absolute: false), false)
        ->assertDontSee(route('farm.dashboard', absolute: false), false)
        ->assertDontSee('signup-popup', false);
});

test('authenticated farm user sees farm name and farm panel link on home', function () {
    $user = User::factory()->create(['name' => 'Manager Finca Andina']);
    $farm = Farm::query()->create([
        'name' => 'Finca Florícola Andina',
        'active' => true,
    ]);

    FarmUser::query()->create([
        'farm_id' => $farm->id,
        'user_id' => $user->id,
        'role' => 'manager',
        'active' => true,
    ]);

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertSee('Finca Florícola Andina', false)
        ->assertSee('Manager Finca Andina', false)
        ->assertSee('MI PANEL', false)
        ->assertDontSee('INICIAR SESIÓN', false)
        ->assertDontSee('CATÁLOGO 2026', false)
        ->assertSee(route('farm.dashboard', absolute: false), false)
        ->assertDontSee(route('admin.dashboard', absolute: false), false)
        ->assertDontSee('signup-popup', false);
});

test('farm user portal dashboard url points to farm not admin', function () {
    $user = User::factory()->create();
    $farm = Farm::query()->create(['name' => 'Portal Farm', 'active' => true]);

    FarmUser::query()->create([
        'farm_id' => $farm->id,
        'user_id' => $user->id,
        'role' => 'manager',
        'active' => true,
    ]);

    expect($user->isFarmUser())->toBeTrue()
        ->and($user->portalDashboardRoute())->toBe('farm.dashboard')
        ->and($user->portalDashboardUrl())->toBe(route('farm.dashboard'));
});

test('admin portal dashboard url points to admin', function () {
    $admin = User::factory()->create();

    expect($admin->isFarmUser())->toBeFalse()
        ->and($admin->portalDashboardRoute())->toBe('admin.dashboard')
        ->and($admin->portalDashboardUrl())->toBe(route('admin.dashboard'));
});

test('logout from home destroys session and returns to home', function () {
    $user = User::factory()->create(['name' => 'Logout User']);

    $this->actingAs($user)
        ->post('/logout')
        ->assertRedirect('/');

    $this->assertGuest();

    $this->get('/')
        ->assertOk()
        ->assertSee('INICIAR SESIÓN', false)
        ->assertDontSee('Logout User', false);
});
