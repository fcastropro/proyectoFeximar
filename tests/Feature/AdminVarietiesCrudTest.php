<?php

use App\Models\Farm;
use App\Models\FarmUser;
use App\Models\FlowerType;
use App\Models\Product;
use App\Models\User;
use App\Models\Variety;

function varietyAdminUser(): User
{
    return User::factory()->create(['active' => true]);
}

function varietyFarmUser(): User
{
    $user = User::factory()->create(['active' => true]);
    $farm = Farm::query()->create([
        'name' => 'Farm Variety Gate',
        'active' => true,
    ]);
    FarmUser::query()->create([
        'farm_id' => $farm->id,
        'user_id' => $user->id,
        'role' => 'manager',
        'active' => true,
    ]);

    return $user;
}

function seedFlowerType(string $suffix = 'A'): FlowerType
{
    return FlowerType::query()->create([
        'name' => "Tipo Flor {$suffix}",
        'active' => true,
    ]);
}

test('admin can list varieties', function () {
    $flowerType = seedFlowerType('List');
    Variety::query()->create([
        'flower_type_id' => $flowerType->id,
        'name' => 'Freedom List',
        'color' => 'Rojo',
        'active' => true,
    ]);

    $this->actingAs(varietyAdminUser())
        ->get(route('admin.varieties.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Varieties/Index')
            ->has('varieties', 1)
            ->where('varieties.0.name', 'Freedom List')
            ->where('varieties.0.flower_type', $flowerType->name)
        );
});

test('admin can create a variety', function () {
    $flowerType = seedFlowerType('Create');

    $this->actingAs(varietyAdminUser())
        ->post(route('admin.varieties.store'), [
            'flower_type_id' => $flowerType->id,
            'name' => 'Explorer Create',
            'color' => 'Naranja',
            'active' => true,
        ])
        ->assertRedirect(route('admin.varieties.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('varieties', [
        'flower_type_id' => $flowerType->id,
        'name' => 'Explorer Create',
        'color' => 'Naranja',
        'active' => 1,
    ]);
});

test('admin can edit a variety', function () {
    $flowerType = seedFlowerType('Edit');
    $variety = Variety::query()->create([
        'flower_type_id' => $flowerType->id,
        'name' => 'Old Name',
        'color' => 'Blanco',
        'active' => true,
    ]);

    $this->actingAs(varietyAdminUser())
        ->put(route('admin.varieties.update', $variety), [
            'flower_type_id' => $flowerType->id,
            'name' => 'New Name',
            'color' => 'Crema',
            'active' => true,
        ])
        ->assertRedirect(route('admin.varieties.index'))
        ->assertSessionHas('success');

    expect($variety->fresh()->name)->toBe('New Name')
        ->and($variety->fresh()->color)->toBe('Crema');
});

test('admin can deactivate a variety', function () {
    $flowerType = seedFlowerType('Deact');
    $variety = Variety::query()->create([
        'flower_type_id' => $flowerType->id,
        'name' => 'To Deactivate',
        'color' => 'Rosa',
        'active' => true,
    ]);

    $this->actingAs(varietyAdminUser())
        ->put(route('admin.varieties.update', $variety), [
            'flower_type_id' => $flowerType->id,
            'name' => 'To Deactivate',
            'color' => 'Rosa',
            'active' => false,
        ])
        ->assertRedirect(route('admin.varieties.index'));

    expect($variety->fresh()->active)->toBeFalse();
});

test('variety create requires mandatory fields', function () {
    $this->actingAs(varietyAdminUser())
        ->post(route('admin.varieties.store'), [])
        ->assertSessionHasErrors(['flower_type_id', 'name', 'color']);
});

test('variety cannot duplicate name within same flower type', function () {
    $flowerType = seedFlowerType('Dup');
    Variety::query()->create([
        'flower_type_id' => $flowerType->id,
        'name' => 'Freedom',
        'color' => 'Rojo',
        'active' => true,
    ]);

    $this->actingAs(varietyAdminUser())
        ->post(route('admin.varieties.store'), [
            'flower_type_id' => $flowerType->id,
            'name' => 'Freedom',
            'color' => 'Rojo oscuro',
            'active' => true,
        ])
        ->assertSessionHasErrors('name');

    $otherType = seedFlowerType('DupOther');

    $this->actingAs(varietyAdminUser())
        ->post(route('admin.varieties.store'), [
            'flower_type_id' => $otherType->id,
            'name' => 'Freedom',
            'color' => 'Rojo',
            'active' => true,
        ])
        ->assertRedirect(route('admin.varieties.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('varieties', [
        'flower_type_id' => $otherType->id,
        'name' => 'Freedom',
    ]);
});

test('non admin cannot access varieties module', function () {
    $this->actingAs(varietyFarmUser())
        ->get(route('admin.varieties.index'))
        ->assertForbidden();
});

test('admin can delete unused variety', function () {
    $flowerType = seedFlowerType('DelOk');
    $variety = Variety::query()->create([
        'flower_type_id' => $flowerType->id,
        'name' => 'Unused Variety',
        'color' => 'Verde',
        'active' => true,
    ]);

    $this->actingAs(varietyAdminUser())
        ->delete(route('admin.varieties.destroy', $variety))
        ->assertRedirect(route('admin.varieties.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('varieties', ['id' => $variety->id]);
});

test('admin cannot delete variety used by products', function () {
    $flowerType = seedFlowerType('DelBlock');
    $variety = Variety::query()->create([
        'flower_type_id' => $flowerType->id,
        'name' => 'Used Variety',
        'color' => 'Rojo',
        'active' => true,
    ]);

    Product::query()->create([
        'name' => 'Producto con variedad',
        'variety_id' => $variety->id,
        'category' => $flowerType->name,
        'variety' => $variety->name,
        'color' => $variety->color,
        'active' => true,
    ]);

    $this->actingAs(varietyAdminUser())
        ->delete(route('admin.varieties.destroy', $variety))
        ->assertRedirect(route('admin.varieties.index'))
        ->assertSessionHas('error', 'No se puede eliminar esta variedad porque está siendo utilizada en productos.');

    $this->assertDatabaseHas('varieties', ['id' => $variety->id]);
});

test('new active variety appears in product catalog selector', function () {
    $flowerType = seedFlowerType('Catalog');

    $this->actingAs(varietyAdminUser())
        ->post(route('admin.varieties.store'), [
            'flower_type_id' => $flowerType->id,
            'name' => 'Catalog New',
            'color' => 'Amarillo',
            'active' => true,
        ])
        ->assertRedirect(route('admin.varieties.index'));

    $variety = Variety::query()
        ->where('flower_type_id', $flowerType->id)
        ->where('name', 'Catalog New')
        ->firstOrFail();

    $this->actingAs(varietyAdminUser())
        ->get(route('admin.catalog.varieties', $flowerType))
        ->assertOk()
        ->assertJsonFragment([
            'id' => $variety->id,
            'name' => 'Catalog New',
            'color' => 'Amarillo',
            'flower_type_id' => $flowerType->id,
        ]);
});
