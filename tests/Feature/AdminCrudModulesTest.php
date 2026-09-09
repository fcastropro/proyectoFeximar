<?php

use App\Models\Buyer;
use App\Models\Country;
use App\Models\Farm;
use App\Models\FarmProduct;
use App\Models\FarmUser;
use App\Models\FlowerType;
use App\Models\Product;
use App\Models\User;
use App\Models\Variety;

test('admin can list and create farms', function () {
    $admin = User::factory()->create(['active' => true]);
    $country = Country::query()->create([
        'name' => 'Ecuador Farms CRUD',
        'iso2' => 'EF',
        'iso3' => 'EFC',
        'phone_code' => '593',
        'active' => true,
    ]);
    $province = \App\Models\Province::query()->create([
        'country_id' => $country->id,
        'name' => 'Pichincha CRUD',
        'active' => true,
    ]);
    $city = \App\Models\City::query()->create([
        'province_id' => $province->id,
        'name' => 'Quito CRUD',
        'active' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.farms.index'))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('admin.farms.create'))
        ->assertOk();

    $this->actingAs($admin)
        ->post(route('admin.farms.store'), [
            'name' => 'Finca CRUD Test',
            'commercial_name' => 'Comercial CRUD',
            'country_id' => $country->id,
            'province_id' => $province->id,
            'city_id' => $city->id,
            'active' => true,
        ])
        ->assertRedirect(route('admin.farms.index'))
        ->assertSessionHas('success');

    $farm = Farm::query()->where('name', 'Finca CRUD Test')->first();
    expect($farm)->not->toBeNull();

    $this->actingAs($admin)
        ->get(route('admin.farms.edit', $farm))
        ->assertOk();

    $this->actingAs($admin)
        ->put(route('admin.farms.update', $farm), [
            'name' => 'Finca CRUD Updated',
            'commercial_name' => 'Comercial CRUD',
            'country_id' => $country->id,
            'province_id' => $province->id,
            'city_id' => $city->id,
            'active' => true,
        ])
        ->assertRedirect(route('admin.farms.index'))
        ->assertSessionHas('success');

    $this->actingAs($admin)
        ->delete(route('admin.farms.destroy', $farm))
        ->assertRedirect(route('admin.farms.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('farms', ['id' => $farm->id]);
});

test('admin cannot delete farm with related farm products', function () {
    $admin = User::factory()->create(['active' => true]);
    $farm = Farm::query()->create(['name' => 'Finca Con Productos', 'active' => true]);
    $product = Product::query()->create(['name' => 'Prod Farm FK', 'active' => true, 'variety' => 'X']);
    FarmProduct::query()->create([
        'farm_id' => $farm->id,
        'product_id' => $product->id,
        'active' => true,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.farms.destroy', $farm))
        ->assertRedirect(route('admin.farms.index'))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('farms', ['id' => $farm->id]);
});

test('admin buyers crud works and blocks unauthorized users', function () {
    $admin = User::factory()->create(['active' => true]);
    $country = Country::query()->create([
        'name' => 'United States Buyers',
        'iso2' => 'UB',
        'iso3' => 'USB',
        'phone_code' => '1',
        'active' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.buyers.index'))
        ->assertOk();

    $this->actingAs($admin)
        ->post(route('admin.buyers.store'), [
            'company_name' => 'Buyer CRUD Co',
            'contact_name' => 'Contact CRUD',
            'email' => 'buyer-crud@test.local',
            'country_id' => $country->id,
            'active' => true,
        ])
        ->assertRedirect(route('admin.buyers.index'))
        ->assertSessionHas('success');

    $buyer = Buyer::query()->where('email', 'buyer-crud@test.local')->first();
    expect($buyer)->not->toBeNull();

    $this->actingAs($admin)
        ->get(route('admin.buyers.edit', $buyer))
        ->assertOk();

    $this->actingAs($admin)
        ->put(route('admin.buyers.update', $buyer), [
            'company_name' => 'Buyer CRUD Updated',
            'contact_name' => 'Contact CRUD',
            'email' => 'buyer-crud@test.local',
            'country_id' => $country->id,
            'active' => true,
        ])
        ->assertRedirect(route('admin.buyers.index'))
        ->assertSessionHas('success');

    $this->actingAs($admin)
        ->delete(route('admin.buyers.destroy', $buyer))
        ->assertRedirect(route('admin.buyers.index'))
        ->assertSessionHas('success');

    $farm = Farm::query()->create(['name' => 'Farm Gate Buyers', 'active' => true]);
    $farmUser = User::factory()->create(['active' => true]);
    FarmUser::query()->create([
        'farm_id' => $farm->id,
        'user_id' => $farmUser->id,
        'role' => 'manager',
        'active' => true,
    ]);

    $this->actingAs($farmUser)
        ->get(route('admin.buyers.index'))
        ->assertForbidden();
});

test('admin farm products index create edit update delete', function () {
    $admin = User::factory()->create(['active' => true]);
    $farm = Farm::query()->create(['name' => 'Finca FP CRUD', 'active' => true]);
    $flowerType = FlowerType::query()->create(['name' => 'Tipo FP', 'active' => true]);
    $variety = Variety::query()->create([
        'flower_type_id' => $flowerType->id,
        'name' => 'Var FP',
        'color' => 'Pink',
        'active' => true,
    ]);
    $product = Product::query()->create([
        'name' => 'Prod FP CRUD',
        'variety_id' => $variety->id,
        'category' => $flowerType->name,
        'variety' => $variety->name,
        'color' => 'Pink',
        'active' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.farm-products.index'))
        ->assertOk();

    $this->actingAs($admin)
        ->post(route('admin.farm-products.store'), [
            'farm_id' => $farm->id,
            'product_id' => $product->id,
            'active' => true,
        ])
        ->assertRedirect(route('admin.farm-products.index'))
        ->assertSessionHas('success');

    $farmProduct = FarmProduct::query()
        ->where('farm_id', $farm->id)
        ->where('product_id', $product->id)
        ->first();

    expect($farmProduct)->not->toBeNull();

    $this->actingAs($admin)
        ->get(route('admin.farm-products.edit', $farmProduct))
        ->assertOk();

    $this->actingAs($admin)
        ->put(route('admin.farm-products.update', $farmProduct), [
            'farm_id' => $farm->id,
            'product_id' => $product->id,
            'active' => false,
        ])
        ->assertRedirect(route('admin.farm-products.index'))
        ->assertSessionHas('success');

    $this->actingAs($admin)
        ->delete(route('admin.farm-products.destroy', $farmProduct))
        ->assertRedirect(route('admin.farm-products.index'))
        ->assertSessionHas('success');
});

test('admin can open cargo agencies and box configs indexes', function () {
    $admin = User::factory()->create(['active' => true]);

    $this->actingAs($admin)->get(route('admin.cargo-agencies.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.box-configs.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.presentations.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.availabilities.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.orders.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.exports.index'))->assertOk();
});
