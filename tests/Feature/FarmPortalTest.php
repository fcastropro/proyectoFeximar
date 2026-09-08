<?php

use App\Models\BoxType;
use App\Models\Farm;
use App\Models\FarmProduct;
use App\Models\FarmProductAvailability;
use App\Models\FarmProductPresentation;
use App\Models\FarmUser;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderFarmFulfillment;
use App\Models\Product;
use App\Models\User;
use App\Services\FarmAvailabilityReservationService;
use App\Services\OrderFulfillmentService;

function makeFarmContext(string $email = 'farm-a@test.local'): array
{
    $user = User::factory()->create(['email' => $email]);
    $farm = Farm::query()->create([
        'name' => 'Farm '.$email,
        'active' => true,
    ]);

    FarmUser::query()->create([
        'farm_id' => $farm->id,
        'user_id' => $user->id,
        'role' => 'manager',
        'active' => true,
    ]);

    $product = Product::query()->create([
        'name' => 'Rosa Test',
        'active' => true,
    ]);

    $farmProduct = FarmProduct::query()->create([
        'farm_id' => $farm->id,
        'product_id' => $product->id,
        'active' => true,
    ]);

    $presentation = FarmProductPresentation::query()->create([
        'farm_product_id' => $farmProduct->id,
        'stem_length_cm' => 50,
        'active' => true,
    ]);

    $availability = FarmProductAvailability::query()->create([
        'farm_product_presentation_id' => $presentation->id,
        'year' => 2026,
        'week_number' => 37,
        'available_stems' => 5000,
        'reserved_stems' => 0,
        'price_per_stem' => 0.45,
        'active' => true,
    ]);

    return compact('user', 'farm', 'presentation', 'availability', 'product');
}

test('farm user cannot access admin panel', function () {
    $ctx = makeFarmContext();

    $this->actingAs($ctx['user'])
        ->get('/admin')
        ->assertForbidden();
});

test('farm dashboard shares auth user and farmPortal for header', function () {
    $ctx = makeFarmContext('farm-header@test.local');

    $this->actingAs($ctx['user'])
        ->get(route('farm.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Farm/Dashboard')
            ->where('auth.user.name', $ctx['user']->name)
            ->where('farmPortal.id', $ctx['farm']->id)
            ->where('farmPortal.name', $ctx['farm']->name)
            ->has('farm.name')
            ->has('kpis'));
});

test('farm user cannot see another farm availability', function () {
    $a = makeFarmContext('farm-a@test.local');
    $b = makeFarmContext('farm-b@test.local');

    $this->actingAs($a['user'])
        ->get(route('farm.availabilities.edit', $b['availability']->id))
        ->assertForbidden();
});

test('farm user cannot create availability for foreign presentation', function () {
    $a = makeFarmContext('farm-a2@test.local');
    $b = makeFarmContext('farm-b2@test.local');

    $this->actingAs($a['user'])
        ->post(route('farm.availabilities.store'), [
            'farm_product_presentation_id' => $b['presentation']->id,
            'year' => 2026,
            'week_number' => 40,
            'available_stems' => 100,
            'active' => true,
        ])
        ->assertSessionHasErrors('farm_product_presentation_id');
});

test('accepting fulfillment reserves stems once', function () {
    $ctx = makeFarmContext('farm-reserve@test.local');
    $boxType = BoxType::query()->create([
        'code' => 'HB',
        'name' => 'Half Box',
        'active' => true,
    ]);

    $buyer = \App\Models\Buyer::query()->create([
        'company_name' => 'Buyer',
        'contact_name' => 'Contact',
        'email' => 'buyer-reserve@test.local',
        'country' => 'US',
        'active' => true,
    ]);

    $order = Order::query()->create([
        'buyer_id' => $buyer->id,
        'status' => 'pending',
        'total' => 900,
    ]);

    OrderDetail::query()->create([
        'order_id' => $order->id,
        'farm_product_availability_id' => $ctx['availability']->id,
        'box_type_id' => $boxType->id,
        'boxes' => 5,
        'stems_per_box' => 200,
        'total_stems' => 1000,
        'unit_price' => 180,
        'subtotal' => 900,
    ]);

    app(OrderFulfillmentService::class)->syncForOrder($order->fresh('details'));

    $fulfillment = OrderFarmFulfillment::query()
        ->where('order_id', $order->id)
        ->where('farm_id', $ctx['farm']->id)
        ->firstOrFail();

    $service = app(FarmAvailabilityReservationService::class);
    $service->reserveOnAccept($fulfillment);

    expect($ctx['availability']->fresh()->reserved_stems)->toBe(1000);
    expect($fulfillment->fresh()->status)->toBe('accepted');
    expect($fulfillment->fresh()->stems_reserved)->toBeTrue();

    expect(fn () => $service->reserveOnAccept($fulfillment->fresh()))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('order exceeding remaining stems is rejected', function () {
    $admin = User::factory()->create();
    $ctx = makeFarmContext('farm-over@test.local');
    $boxType = BoxType::query()->create([
        'code' => 'FULL',
        'name' => 'Full',
        'active' => true,
    ]);

    \App\Models\PresentationBoxConfig::query()->create([
        'farm_product_presentation_id' => $ctx['presentation']->id,
        'box_type_id' => $boxType->id,
        'stems_per_box' => 1600,
        'active' => true,
    ]);

    $buyer = \App\Models\Buyer::query()->create([
        'company_name' => 'Buyer Over',
        'contact_name' => 'Contact',
        'email' => 'buyer-over@test.local',
        'country' => 'US',
        'active' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.orders.store'), [
            'buyer_id' => $buyer->id,
            'status' => 'pending',
            'notes' => null,
            'details' => [
                [
                    'farm_product_availability_id' => $ctx['availability']->id,
                    'box_type_id' => $boxType->id,
                    'quantity' => 4,
                    'unit_price' => 100,
                ],
            ],
        ])
        ->assertSessionHasErrors();
});

test('farm finance amount is calculated from farm lines only', function () {
    $ctx = makeFarmContext('farm-fin@test.local');
    $boxType = BoxType::query()->create([
        'code' => 'QB',
        'name' => 'Quarter',
        'active' => true,
    ]);

    $buyer = \App\Models\Buyer::query()->create([
        'company_name' => 'Buyer Fin',
        'contact_name' => 'Contact',
        'email' => 'buyer-fin@test.local',
        'country' => 'US',
        'active' => true,
    ]);

    $order = Order::query()->create([
        'buyer_id' => $buyer->id,
        'status' => 'pending',
        'total' => 500,
    ]);

    OrderDetail::query()->create([
        'order_id' => $order->id,
        'farm_product_availability_id' => $ctx['availability']->id,
        'box_type_id' => $boxType->id,
        'boxes' => 2,
        'stems_per_box' => 100,
        'total_stems' => 200,
        'unit_price' => 250,
        'subtotal' => 500,
    ]);

    app(OrderFulfillmentService::class)->syncForOrder($order->fresh('details'));

    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.farm-finances.store'), [
            'order_id' => $order->id,
            'farm_id' => $ctx['farm']->id,
            'payment_condition' => 'cash',
        ])
        ->assertRedirect(route('admin.farm-finances.index'));

    $this->assertDatabaseHas('farm_order_finances', [
        'order_id' => $order->id,
        'farm_id' => $ctx['farm']->id,
        'amount' => 500,
    ]);
});
