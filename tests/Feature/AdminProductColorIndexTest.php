<?php

use App\Models\FlowerType;
use App\Models\Product;
use App\Models\User;
use App\Models\Variety;

test('product index shows products.color not catalogVariety.color', function () {
    $admin = User::factory()->create(['active' => true]);

    $flowerType = FlowerType::query()->create([
        'name' => 'Rosa Color Bug',
        'active' => true,
    ]);

    $variety = Variety::query()->create([
        'flower_type_id' => $flowerType->id,
        'name' => 'Freedom',
        'color' => 'Rojo',
        'active' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.products.store'), [
            'name' => 'Rosa Mondial',
            'flower_type_id' => $flowerType->id,
            'variety_id' => $variety->id,
            'color' => 'Blanco',
            'description' => 'Color propio del producto',
            'active' => true,
        ])
        ->assertRedirect(route('admin.products.index'))
        ->assertSessionHas('success');

    $product = Product::query()->where('name', 'Rosa Mondial')->first();
    expect($product)->not->toBeNull()
        ->and($product->color)->toBe('Blanco')
        ->and($variety->fresh()->color)->toBe('Rojo');

    $this->actingAs($admin)
        ->get(route('admin.products.edit', $product))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Products/Edit')
            ->where('product.color', 'Blanco')
        );

    $this->actingAs($admin)
        ->get(route('admin.products.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Products/Index')
            ->where('products.0.color', 'Blanco')
        );

    $this->actingAs($admin)
        ->put(route('admin.products.update', $product), [
            'name' => 'Rosa Mondial',
            'flower_type_id' => $flowerType->id,
            'variety_id' => $variety->id,
            'color' => 'Amarillo',
            'description' => 'Color propio del producto',
            'active' => true,
        ])
        ->assertRedirect(route('admin.products.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'color' => 'Amarillo',
    ]);

    $this->assertDatabaseHas('varieties', [
        'id' => $variety->id,
        'color' => 'Rojo',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.products.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Products/Index')
            ->where('products.0.id', $product->id)
            ->where('products.0.color', 'Amarillo')
        );
});
