<?php

use App\Models\Buyer;
use App\Models\BuyerUser;
use App\Models\Farm;
use App\Models\FarmUser;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('admin can create farm user with hashed password', function () {
    $admin = User::factory()->create(['active' => true]);
    $farm = Farm::query()->create(['name' => 'Finca Users', 'active' => true]);

    $this->actingAs($admin)
        ->post(route('admin.users.farms.store'), [
            'farm_id' => $farm->id,
            'name' => 'Operador Uno',
            'email' => 'operador.uno@test.local',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
            'role' => 'operator',
            'active' => true,
        ])
        ->assertRedirect(route('admin.users.farms.index'));

    $user = User::query()->where('email', 'operador.uno@test.local')->first();
    expect($user)->not->toBeNull()
        ->and($user->active)->toBeTrue()
        ->and(Hash::check('Secret123!', $user->password))->toBeTrue()
        ->and($user->isFarmUser())->toBeTrue()
        ->and($user->isAdminUser())->toBeFalse()
        ->and($user->isBuyerUser())->toBeFalse();

    expect(FarmUser::query()->where('user_id', $user->id)->where('farm_id', $farm->id)->exists())->toBeTrue();
});

test('admin can create buyer user', function () {
    $admin = User::factory()->create(['active' => true]);
    $buyer = Buyer::query()->create([
        'company_name' => 'Buyer Co Users',
        'contact_name' => 'Contact',
        'email' => 'buyerco@test.local',
        'country' => 'USA',
        'active' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.users.buyers.store'), [
            'buyer_id' => $buyer->id,
            'name' => 'Buyer Manager',
            'email' => 'buyer.manager@test.local',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
            'role' => 'manager',
            'active' => true,
            'credit_allowed' => true,
            'credit_days_default' => 30,
        ])
        ->assertRedirect(route('admin.users.buyers.index'));

    $user = User::query()->where('email', 'buyer.manager@test.local')->first();
    expect($user->isBuyerUser())->toBeTrue()
        ->and($user->portalDashboardRoute())->toBe('buyer.dashboard');
});

test('farm user cannot access admin users module', function () {
    $farm = Farm::query()->create(['name' => 'Farm Gate', 'active' => true]);
    $farmUser = User::factory()->create(['active' => true]);
    FarmUser::query()->create([
        'farm_id' => $farm->id,
        'user_id' => $farmUser->id,
        'role' => 'manager',
        'active' => true,
    ]);

    $this->actingAs($farmUser)
        ->get(route('admin.users.admins.index'))
        ->assertForbidden();
});

test('buyer user cannot access admin users module', function () {
    $buyer = Buyer::query()->create([
        'company_name' => 'Buyer Gate',
        'contact_name' => 'C',
        'email' => 'gate@test.local',
        'country' => 'USA',
        'active' => true,
    ]);
    $buyerUser = User::factory()->create(['active' => true]);
    BuyerUser::query()->create([
        'buyer_id' => $buyer->id,
        'user_id' => $buyerUser->id,
        'role' => 'buyer',
        'active' => true,
    ]);

    $this->actingAs($buyerUser)
        ->get(route('admin.users.buyers.index'))
        ->assertForbidden();
});

test('active farm user redirects to farm dashboard', function () {
    $farm = Farm::query()->create(['name' => 'Redirect Farm', 'active' => true]);
    $user = User::factory()->create(['active' => true]);
    FarmUser::query()->create([
        'farm_id' => $farm->id,
        'user_id' => $user->id,
        'role' => 'manager',
        'active' => true,
    ]);

    expect($user->fresh()->portalDashboardRoute())->toBe('farm.dashboard');

    $this->actingAs($user)
        ->get(route('farm.dashboard'))
        ->assertOk();
});

test('active buyer redirects to buyer dashboard', function () {
    $buyer = Buyer::query()->create([
        'company_name' => 'Redirect Buyer',
        'contact_name' => 'C',
        'email' => 'redirect-buyer@test.local',
        'country' => 'USA',
        'active' => true,
    ]);
    $user = User::factory()->create(['active' => true]);
    BuyerUser::query()->create([
        'buyer_id' => $buyer->id,
        'user_id' => $user->id,
        'role' => 'manager',
        'active' => true,
    ]);

    expect($user->fresh()->portalDashboardRoute())->toBe('buyer.dashboard');
});

test('admin redirects to admin dashboard', function () {
    $admin = User::factory()->create(['active' => true]);
    expect($admin->isAdminUser())->toBeTrue()
        ->and($admin->portalDashboardRoute())->toBe('admin.dashboard');
});

test('user cannot have incompatible farm and buyer profiles', function () {
    $farm = Farm::query()->create(['name' => 'Conflict Farm', 'active' => true]);
    $user = User::factory()->create(['active' => true]);
    FarmUser::query()->create([
        'farm_id' => $farm->id,
        'user_id' => $user->id,
        'role' => 'operator',
        'active' => true,
    ]);

    $service = app(\App\Services\Admin\UserAccessService::class);

    expect(fn () => $service->assertCanBeBuyerUser($user->fresh()))
        ->toThrow(\Illuminate\Validation\ValidationException::class);

    expect($user->fresh()->isFarmUser())->toBeTrue()
        ->and($user->fresh()->isBuyerUser())->toBeFalse()
        ->and($user->fresh()->isAdminUser())->toBeFalse();
});

test('deactivating farm user blocks portal access', function () {
    $farm = Farm::query()->create(['name' => 'Deactivate Farm', 'active' => true]);
    $user = User::factory()->create(['active' => true]);
    $link = FarmUser::query()->create([
        'farm_id' => $farm->id,
        'user_id' => $user->id,
        'role' => 'manager',
        'active' => true,
    ]);

    $admin = User::factory()->create(['active' => true]);
    $this->actingAs($admin)
        ->post(route('admin.users.farms.toggle-active', $link))
        ->assertRedirect();

    $user->refresh();
    expect($user->active)->toBeFalse()
        ->and($user->isFarmUser())->toBeFalse();

    $this->actingAs($user)
        ->get(route('farm.dashboard'))
        ->assertForbidden();
});

test('cannot deactivate the last active admin', function () {
    $admin = User::factory()->create(['active' => true, 'email' => 'only-admin@test.local']);

    $this->actingAs($admin)
        ->post(route('admin.users.admins.toggle-active', $admin))
        ->assertSessionHasErrors('active');

    expect($admin->fresh()->active)->toBeTrue();
});

test('inactive account cannot login', function () {
    $user = User::factory()->create([
        'email' => 'inactive.login@test.local',
        'password' => 'Secret123!',
        'active' => false,
    ]);

    $this->post(route('login'), [
        'email' => 'inactive.login@test.local',
        'password' => 'Secret123!',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});
