<?php

use App\Models\BoxType;
use App\Models\Buyer;
use App\Models\Country;
use App\Models\Farm;
use App\Models\FarmOrderFinance;
use App\Models\FarmPayment;
use App\Models\FarmProduct;
use App\Models\FarmProductAvailability;
use App\Models\FarmProductPresentation;
use App\Models\FarmUser;
use App\Models\FlowerType;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderFarmFulfillment;
use App\Models\PresentationBoxConfig;
use App\Models\Product;
use App\Models\User;
use App\Models\Variety;

function pendingAuditAdmin(): User
{
    return User::factory()->create(['active' => true]);
}

/**
 * @return array{
 *   country:Country,
 *   buyer:Buyer,
 *   farm:Farm,
 *   flowerType:FlowerType,
 *   variety:Variety,
 *   product:Product,
 *   farmProduct:FarmProduct,
 *   presentation:FarmProductPresentation,
 *   availability:FarmProductAvailability,
 *   boxType:BoxType,
 *   boxConfig:PresentationBoxConfig
 * }
 */
function seedPendingCrudGraph(string $suffix = 'A'): array
{
    $country = Country::query()->create([
        'name' => "Country {$suffix}",
        'iso2' => substr(strtoupper("C{$suffix}"), 0, 2),
        'iso3' => substr(strtoupper("CT{$suffix}"), 0, 3),
        'phone_code' => '593',
        'active' => true,
    ]);

    $buyer = Buyer::query()->create([
        'company_name' => "Buyer {$suffix}",
        'contact_name' => "Contact {$suffix}",
        'email' => "buyer-{$suffix}@test.local",
        'country_id' => $country->id,
        'country' => $country->name,
        'active' => true,
    ]);

    $farm = Farm::query()->create([
        'name' => "Farm {$suffix}",
        'active' => true,
    ]);

    $flowerType = FlowerType::query()->create([
        'name' => "Type {$suffix}",
        'active' => true,
    ]);

    $variety = Variety::query()->create([
        'flower_type_id' => $flowerType->id,
        'name' => "Variety {$suffix}",
        'color' => 'Red',
        'active' => true,
    ]);

    $product = Product::query()->create([
        'name' => "Product {$suffix}",
        'variety_id' => $variety->id,
        'category' => $flowerType->name,
        'variety' => $variety->name,
        'color' => $variety->color,
        'active' => true,
    ]);

    $farmProduct = FarmProduct::query()->create([
        'farm_id' => $farm->id,
        'product_id' => $product->id,
        'active' => true,
    ]);

    $presentation = FarmProductPresentation::query()->create([
        'farm_product_id' => $farmProduct->id,
        'stem_length_cm' => 60,
        'stems_per_bunch' => 25,
        'price_per_stem' => 0.45,
        'price_per_bunch' => 11.25,
        'active' => true,
    ]);

    $availability = FarmProductAvailability::query()->create([
        'farm_product_presentation_id' => $presentation->id,
        'year' => 2026,
        'week_number' => 40,
        'available_stems' => 2000,
        'reserved_stems' => 0,
        'price_per_stem' => 0.5,
        'price_per_bunch' => 12.5,
        'active' => true,
    ]);

    $boxType = BoxType::query()->create([
        'code' => "BX{$suffix}",
        'name' => "Box {$suffix}",
        'active' => true,
    ]);

    $boxConfig = PresentationBoxConfig::query()->create([
        'farm_product_presentation_id' => $presentation->id,
        'box_type_id' => $boxType->id,
        'stems_per_box' => 250,
        'bunches_per_box' => 10,
        'active' => true,
    ]);

    return compact(
        'country',
        'buyer',
        'farm',
        'flowerType',
        'variety',
        'product',
        'farmProduct',
        'presentation',
        'availability',
        'boxType',
        'boxConfig',
    );
}

function createOrderWithDetail(Buyer $buyer, FarmProductAvailability $availability, BoxType $boxType): Order
{
    $order = Order::query()->create([
        'buyer_id' => $buyer->id,
        'status' => 'pending',
        'total' => 100.00,
    ]);

    OrderDetail::query()->create([
        'order_id' => $order->id,
        'farm_product_availability_id' => $availability->id,
        'box_type_id' => $boxType->id,
        'boxes' => 2,
        'stems_per_box' => 250,
        'total_stems' => 500,
        'unit_price' => 50,
        'subtotal' => 100,
    ]);

    OrderFarmFulfillment::query()->create([
        'order_id' => $order->id,
        'farm_id' => $availability->presentation->farmProduct->farm_id,
        'status' => 'pending',
        'received_at' => now(),
    ]);

    return $order;
}

test('presentations index create edit update delete and validation', function () {
    $admin = pendingAuditAdmin();
    $graph = seedPendingCrudGraph('P1');

    $this->actingAs($admin)
        ->get(route('admin.presentations.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/Presentations/Index'));

    $this->actingAs($admin)
        ->get(route('admin.presentations.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Presentations/Create')
            ->has('farmProducts')
        );

    $this->actingAs($admin)
        ->post(route('admin.presentations.store'), [
            'farm_product_id' => $graph['farmProduct']->id,
            'stem_length_cm' => 70,
            'stems_per_bunch' => 20,
            'price_per_stem' => 0.61,
            'price_per_bunch' => 12.2,
            'active' => true,
        ])
        ->assertRedirect(route('admin.presentations.index'))
        ->assertSessionHas('success');

    $created = FarmProductPresentation::query()
        ->where('farm_product_id', $graph['farmProduct']->id)
        ->where('stem_length_cm', 70)
        ->first();
    expect($created)->not->toBeNull();

    $this->actingAs($admin)
        ->get(route('admin.presentations.edit', $created))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Presentations/Edit')
            ->where('presentation.id', $created->id)
            ->where('presentation.farm_product_id', $graph['farmProduct']->id)
        );

    $this->actingAs($admin)
        ->put(route('admin.presentations.update', $created), [
            'farm_product_id' => $graph['farmProduct']->id,
            'stem_length_cm' => 75,
            'stems_per_bunch' => null,
            'price_per_stem' => 0.7,
            'price_per_bunch' => null,
            'active' => false,
        ])
        ->assertRedirect(route('admin.presentations.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('farm_product_presentations', [
        'id' => $created->id,
        'stem_length_cm' => 75,
        'stems_per_bunch' => null,
        'active' => false,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.presentations.store'), [
            'farm_product_id' => $graph['farmProduct']->id,
            'stem_length_cm' => '',
        ])
        ->assertSessionHasErrors(['stem_length_cm']);

    $this->actingAs($admin)
        ->delete(route('admin.presentations.destroy', $created))
        ->assertRedirect(route('admin.presentations.index'))
        ->assertSessionHas('success');
});

test('availabilities index create edit update delete and fk restriction', function () {
    $admin = pendingAuditAdmin();
    $graph = seedPendingCrudGraph('A1');

    $this->actingAs($admin)
        ->get(route('admin.availabilities.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/Availabilities/Index'));

    $this->actingAs($admin)
        ->get(route('admin.availabilities.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Availabilities/Create')
            ->has('presentations')
        );

    $this->actingAs($admin)
        ->post(route('admin.availabilities.store'), [
            'farm_product_presentation_id' => $graph['presentation']->id,
            'year' => 2026,
            'week_number' => 41,
            'available_stems' => 1500,
            'price_per_stem' => 0.52,
            'price_per_bunch' => 13.5,
            'active' => true,
        ])
        ->assertRedirect(route('admin.availabilities.index'))
        ->assertSessionHas('success');

    $created = FarmProductAvailability::query()
        ->where('farm_product_presentation_id', $graph['presentation']->id)
        ->where('week_number', 41)
        ->first();
    expect($created)->not->toBeNull();

    $this->actingAs($admin)
        ->get(route('admin.availabilities.edit', $created))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Availabilities/Edit')
            ->where('availability.id', $created->id)
            ->where('availability.farm_product_presentation_id', $graph['presentation']->id)
        );

    $this->actingAs($admin)
        ->put(route('admin.availabilities.update', $created), [
            'farm_product_presentation_id' => $graph['presentation']->id,
            'year' => 2026,
            'week_number' => 42,
            'available_stems' => 1800,
            'price_per_stem' => null,
            'price_per_bunch' => 15.5,
            'active' => false,
        ])
        ->assertRedirect(route('admin.availabilities.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('farm_product_availabilities', [
        'id' => $created->id,
        'week_number' => 42,
        'available_stems' => 1800,
        'price_per_stem' => null,
        'active' => false,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.availabilities.store'), [
            'farm_product_presentation_id' => $graph['presentation']->id,
            'year' => '',
            'week_number' => '',
            'available_stems' => '',
        ])
        ->assertSessionHasErrors(['year', 'week_number', 'available_stems']);

    $order = createOrderWithDetail($graph['buyer'], $graph['availability'], $graph['boxType']);
    expect($order)->not->toBeNull();

    $this->actingAs($admin)
        ->delete(route('admin.availabilities.destroy', $graph['availability']))
        ->assertRedirect(route('admin.availabilities.index'))
        ->assertSessionHas('error');
});

test('box configs index create edit update delete and validation', function () {
    $admin = pendingAuditAdmin();
    $graph = seedPendingCrudGraph('B1');

    $this->actingAs($admin)
        ->get(route('admin.box-configs.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/BoxConfigs/Index'));

    $this->actingAs($admin)
        ->get(route('admin.box-configs.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/BoxConfigs/Create')
            ->has('presentations')
            ->has('boxTypes')
        );

    $newBoxType = BoxType::query()->create(['code' => 'BX2', 'name' => 'Box 2', 'active' => true]);

    $this->actingAs($admin)
        ->post(route('admin.box-configs.store'), [
            'farm_product_presentation_id' => $graph['presentation']->id,
            'box_type_id' => $newBoxType->id,
            'stems_per_box' => 300,
            'bunches_per_box' => null,
            'active' => true,
        ])
        ->assertRedirect(route('admin.box-configs.index'))
        ->assertSessionHas('success');

    $created = PresentationBoxConfig::query()
        ->where('farm_product_presentation_id', $graph['presentation']->id)
        ->where('box_type_id', $newBoxType->id)
        ->first();
    expect($created)->not->toBeNull();

    $this->actingAs($admin)
        ->get(route('admin.box-configs.edit', $created))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/BoxConfigs/Edit')
            ->where('boxConfig.id', $created->id)
        );

    $this->actingAs($admin)
        ->put(route('admin.box-configs.update', $created), [
            'farm_product_presentation_id' => $graph['presentation']->id,
            'box_type_id' => $newBoxType->id,
            'stems_per_box' => 320,
            'bunches_per_box' => 12,
            'active' => false,
        ])
        ->assertRedirect(route('admin.box-configs.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('presentation_box_configs', [
        'id' => $created->id,
        'stems_per_box' => 320,
        'bunches_per_box' => 12,
        'active' => false,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.box-configs.store'), [
            'farm_product_presentation_id' => '',
            'box_type_id' => '',
            'stems_per_box' => 0,
        ])
        ->assertSessionHasErrors(['farm_product_presentation_id', 'box_type_id', 'stems_per_box']);

    $this->actingAs($admin)
        ->delete(route('admin.box-configs.destroy', $created))
        ->assertRedirect(route('admin.box-configs.index'))
        ->assertSessionHas('success');
});

test('orders index create store show edit update delete and validations', function () {
    $admin = pendingAuditAdmin();
    $graph = seedPendingCrudGraph('O1');

    $this->actingAs($admin)
        ->get(route('admin.orders.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/Orders/Index'));

    $this->actingAs($admin)
        ->get(route('admin.orders.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Orders/Create')
            ->has('buyers')
            ->has('availabilities')
            ->has('statuses')
        );

    $this->actingAs($admin)
        ->post(route('admin.orders.store'), [
            'buyer_id' => $graph['buyer']->id,
            'status' => 'pending',
            'notes' => 'Order from test',
            'details' => [[
                'farm_product_availability_id' => $graph['availability']->id,
                'box_type_id' => $graph['boxType']->id,
                'quantity' => 2,
                'unit_price' => 55.5,
            ]],
        ])
        ->assertRedirect(route('admin.orders.index'))
        ->assertSessionHas('success');

    $order = Order::query()->latest('id')->first();
    expect($order)->not->toBeNull();
    $this->assertDatabaseHas('order_details', [
        'order_id' => $order->id,
        'farm_product_availability_id' => $graph['availability']->id,
        'box_type_id' => $graph['boxType']->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.orders.show', $order))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Orders/Show')
            ->where('order.id', $order->id)
        );

    $this->actingAs($admin)
        ->get(route('admin.orders.edit', $order))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Orders/Edit')
            ->where('order.id', $order->id)
            ->has('availabilities')
        );

    $this->actingAs($admin)
        ->put(route('admin.orders.update', $order), [
            'buyer_id' => $graph['buyer']->id,
            'status' => 'confirmed',
            'notes' => null,
            'details' => [[
                'farm_product_availability_id' => $graph['availability']->id,
                'box_type_id' => $graph['boxType']->id,
                'quantity' => 1,
                'unit_price' => 60,
            ]],
        ])
        ->assertRedirect(route('admin.orders.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => 'confirmed',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.orders.store'), [
            'buyer_id' => '',
            'status' => '',
            'details' => [],
        ])
        ->assertSessionHasErrors(['buyer_id', 'status', 'details']);

    $this->actingAs($admin)
        ->delete(route('admin.orders.destroy', $order))
        ->assertRedirect(route('admin.orders.index'))
        ->assertSessionHas('success');
});

test('finance index create store show payment and validations', function () {
    $admin = pendingAuditAdmin();
    $graph = seedPendingCrudGraph('F1');
    $order = createOrderWithDetail($graph['buyer'], $graph['availability'], $graph['boxType']);

    $this->actingAs($admin)
        ->get(route('admin.farm-finances.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/FarmFinances/Index'));

    $this->actingAs($admin)
        ->get(route('admin.farm-finances.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/FarmFinances/Create')
            ->has('orders')
            ->has('conditions')
        );

    $this->actingAs($admin)
        ->get(route('admin.farm-finances.order-options', $order))
        ->assertOk();

    $this->actingAs($admin)
        ->post(route('admin.farm-finances.store'), [
            'order_id' => $order->id,
            'farm_id' => $graph['farm']->id,
            'payment_condition' => 'credit',
            'credit_days' => 30,
        ])
        ->assertRedirect(route('admin.farm-finances.index'))
        ->assertSessionHas('success');

    $finance = FarmOrderFinance::query()
        ->where('order_id', $order->id)
        ->where('farm_id', $graph['farm']->id)
        ->first();
    expect($finance)->not->toBeNull();

    $this->actingAs($admin)
        ->get(route('admin.farm-finances.show', $finance))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/FarmFinances/Show')
            ->where('finance.id', $finance->id)
        );

    $this->actingAs($admin)
        ->post(route('admin.farm-finances.payments.store', $finance), [
            'amount' => 20,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'transfer',
            'reference' => 'TRX-001',
        ])
        ->assertSessionHas('success');

    $this->assertDatabaseHas('farm_payments', [
        'farm_order_finance_id' => $finance->id,
        'amount' => 20,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.farm-finances.store'), [
            'order_id' => '',
            'farm_id' => '',
            'payment_condition' => '',
        ])
        ->assertSessionHasErrors(['order_id', 'farm_id', 'payment_condition']);

    expect(FarmPayment::query()->count())->toBeGreaterThan(0);
});

test('non admin user cannot access pending admin modules', function () {
    $graph = seedPendingCrudGraph('N1');
    $farmUser = User::factory()->create(['active' => true]);
    FarmUser::query()->create([
        'farm_id' => $graph['farm']->id,
        'user_id' => $farmUser->id,
        'role' => 'manager',
        'active' => true,
    ]);

    $this->actingAs($farmUser)->get(route('admin.presentations.index'))->assertForbidden();
    $this->actingAs($farmUser)->get(route('admin.availabilities.index'))->assertForbidden();
    $this->actingAs($farmUser)->get(route('admin.box-configs.index'))->assertForbidden();
    $this->actingAs($farmUser)->get(route('admin.orders.index'))->assertForbidden();
    $this->actingAs($farmUser)->get(route('admin.farm-finances.index'))->assertForbidden();
});

