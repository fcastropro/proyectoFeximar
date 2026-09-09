<?php

use App\Models\Farm;
use App\Models\FarmProduct;
use App\Models\FarmUser;
use App\Models\FlowerType;
use App\Models\Product;
use App\Models\User;
use App\Models\Variety;

function adminUser(): User
{
    return User::factory()->create(['active' => true]);
}

function seedCatalogProduct(string $suffix = 'A'): array
{
    $flowerType = FlowerType::query()->create([
        'name' => "Tipo {$suffix}",
        'active' => true,
    ]);

    $variety = Variety::query()->create([
        'flower_type_id' => $flowerType->id,
        'name' => "Variedad {$suffix}",
        'color' => 'Rojo',
        'active' => true,
    ]);

    $product = Product::query()->create([
        'name' => "Producto {$suffix}",
        'variety_id' => $variety->id,
        'category' => $flowerType->name,
        'variety' => $variety->name,
        'color' => $variety->color,
        'description' => 'Desc',
        'active' => true,
    ]);

    return compact('flowerType', 'variety', 'product');
}

test('admin can list products', function () {
    seedCatalogProduct('List');
    $admin = adminUser();

    $this->actingAs($admin)
        ->get(route('admin.products.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Products/Index')
            ->has('products', 1)
        );
});

test('admin can open product create form', function () {
    FlowerType::query()->create(['name' => 'Rosa Create', 'active' => true]);
    $admin = adminUser();

    $this->actingAs($admin)
        ->get(route('admin.products.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Products/Create')
            ->has('flowerTypes')
        );
});

test('admin can create a product', function () {
    ['flowerType' => $flowerType, 'variety' => $variety] = seedCatalogProduct('Tmp');
    Product::query()->where('name', 'Producto Tmp')->delete();

    $admin = adminUser();

    $this->actingAs($admin)
        ->post(route('admin.products.store'), [
            'name' => 'Rosa Freedom Nueva',
            'flower_type_id' => $flowerType->id,
            'variety_id' => $variety->id,
            'color' => 'Rojo intenso',
            'description' => 'Producto de prueba',
            'active' => true,
        ])
        ->assertRedirect(route('admin.products.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('products', [
        'name' => 'Rosa Freedom Nueva',
        'variety_id' => $variety->id,
        'variety' => $variety->name,
        'category' => $flowerType->name,
        'active' => true,
    ]);
});

test('admin can edit a product without server error', function () {
    ['product' => $product, 'flowerType' => $flowerType, 'variety' => $variety] = seedCatalogProduct('Edit');
    $admin = adminUser();

    $this->actingAs($admin)
        ->get(route('admin.products.edit', $product))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Products/Edit')
            ->where('product.id', $product->id)
            ->where('product.variety_id', $variety->id)
            ->where('product.flower_type_id', $flowerType->id)
            ->has('flowerTypes')
            ->has('initialVarieties')
        );
});

test('admin can update a product', function () {
    ['product' => $product, 'flowerType' => $flowerType, 'variety' => $variety] = seedCatalogProduct('Upd');
    $admin = adminUser();

    $this->actingAs($admin)
        ->put(route('admin.products.update', $product), [
            'name' => 'Producto Actualizado',
            'flower_type_id' => $flowerType->id,
            'variety_id' => $variety->id,
            'color' => 'Blanco',
            'description' => 'Actualizado',
            'active' => false,
        ])
        ->assertRedirect(route('admin.products.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'Producto Actualizado',
        'color' => 'Blanco',
        'active' => false,
    ]);
});

test('admin can delete a product without dependencies', function () {
    ['product' => $product] = seedCatalogProduct('Del');
    $admin = adminUser();

    $this->actingAs($admin)
        ->delete(route('admin.products.destroy', $product))
        ->assertRedirect(route('admin.products.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('products', ['id' => $product->id]);
});

test('admin cannot delete product with farm product relations', function () {
    ['product' => $product] = seedCatalogProduct('FK');
    $farm = Farm::query()->create(['name' => 'Finca FK', 'active' => true]);
    FarmProduct::query()->create([
        'farm_id' => $farm->id,
        'product_id' => $product->id,
        'active' => true,
    ]);
    $admin = adminUser();

    $this->actingAs($admin)
        ->delete(route('admin.products.destroy', $product))
        ->assertRedirect(route('admin.products.index'))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('products', ['id' => $product->id]);
});

test('product validation rejects missing required fields', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->post(route('admin.products.store'), [
            'name' => '',
            'active' => true,
        ])
        ->assertSessionHasErrors(['name', 'flower_type_id', 'variety_id']);
});

test('unauthorized farm user cannot access products admin', function () {
    $farm = Farm::query()->create(['name' => 'Farm Gate Products', 'active' => true]);
    $farmUser = User::factory()->create(['active' => true]);
    FarmUser::query()->create([
        'farm_id' => $farm->id,
        'user_id' => $farmUser->id,
        'role' => 'manager',
        'active' => true,
    ]);

    $this->actingAs($farmUser)
        ->get(route('admin.products.index'))
        ->assertForbidden();
});

test('guest cannot access products admin', function () {
    $this->get(route('admin.products.index'))
        ->assertRedirect(route('login'));
});
