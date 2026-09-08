<?php

use App\Models\BoxType;
use App\Models\Buyer;
use App\Models\BuyerUser;
use App\Models\Farm;
use App\Models\FarmProduct;
use App\Models\FarmProductAvailability;
use App\Models\FarmProductPresentation;
use App\Models\FarmUser;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\User;
use App\Services\Admin\AdminDashboardService;
use Carbon\Carbon;

function seedOrderFixture(): array
{
    $farmA = Farm::query()->create(['name' => 'Finca Alpha PDF', 'active' => true]);
    $farmB = Farm::query()->create(['name' => 'Finca Beta PDF', 'active' => true]);
    $product = Product::query()->create(['name' => 'Rosa PDF', 'active' => true, 'variety' => 'Freedom']);
    $fpA = FarmProduct::query()->create(['farm_id' => $farmA->id, 'product_id' => $product->id, 'active' => true]);
    $fpB = FarmProduct::query()->create(['farm_id' => $farmB->id, 'product_id' => $product->id, 'active' => true]);
    $presA = FarmProductPresentation::query()->create([
        'farm_product_id' => $fpA->id,
        'stem_length_cm' => 50,
        'stems_per_bunch' => 25,
        'active' => true,
    ]);
    $presB = FarmProductPresentation::query()->create([
        'farm_product_id' => $fpB->id,
        'stem_length_cm' => 60,
        'stems_per_bunch' => 25,
        'active' => true,
    ]);
    $avA = FarmProductAvailability::query()->create([
        'farm_product_presentation_id' => $presA->id,
        'year' => 2026,
        'week_number' => 10,
        'available_stems' => 1000,
        'reserved_stems' => 0,
        'price_per_stem' => 0.4,
        'active' => true,
    ]);
    $avB = FarmProductAvailability::query()->create([
        'farm_product_presentation_id' => $presB->id,
        'year' => 2026,
        'week_number' => 10,
        'available_stems' => 800,
        'reserved_stems' => 0,
        'price_per_stem' => 0.5,
        'active' => true,
    ]);
    $box = BoxType::query()->create(['code' => 'QB', 'name' => 'QB', 'active' => true]);
    $buyer = Buyer::query()->create([
        'company_name' => 'Buyer PDF Co',
        'contact_name' => 'Contact',
        'email' => 'buyer-pdf@test.local',
        'country' => 'United States',
        'active' => true,
    ]);

    $order = Order::query()->create([
        'buyer_id' => $buyer->id,
        'status' => 'confirmed',
        'total' => 200,
        'payment_condition' => 'cash',
        'shipping_method' => 'air',
    ]);
    $order->forceFill([
        'created_at' => Carbon::create(2026, 3, 10, 10),
        'updated_at' => Carbon::create(2026, 3, 10, 10),
    ])->saveQuietly();

    OrderDetail::query()->create([
        'order_id' => $order->id,
        'farm_product_availability_id' => $avA->id,
        'box_type_id' => $box->id,
        'bunches' => 4,
        'stems_per_bunch' => 25,
        'boxes' => 1,
        'stems_per_box' => 100,
        'total_stems' => 100,
        'price_per_stem' => 0.4,
        'unit_price' => 0.4,
        'subtotal' => 40,
    ]);
    OrderDetail::query()->create([
        'order_id' => $order->id,
        'farm_product_availability_id' => $avB->id,
        'box_type_id' => $box->id,
        'bunches' => 8,
        'stems_per_bunch' => 25,
        'boxes' => 2,
        'stems_per_box' => 100,
        'total_stems' => 200,
        'price_per_stem' => 0.5,
        'unit_price' => 0.5,
        'subtotal' => 100,
    ]);

    return compact('farmA', 'farmB', 'order', 'buyer');
}

test('admin dashboard returns real kpi payload', function () {
    $admin = User::factory()->create();
    seedOrderFixture();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Dashboard')
            ->has('commercial')
            ->has('operations')
            ->has('offer')
            ->has('alerts'));

    $payload = app(AdminDashboardService::class)->build();
    expect($payload['commercial']['orders_month'])->toBeGreaterThanOrEqual(0);
    expect($payload['offer']['active_farms'])->toBeGreaterThanOrEqual(2);
});

test('admin can download global farms pdf and order pdf', function () {
    $admin = User::factory()->create();
    ['order' => $order] = seedOrderFixture();

    $this->actingAs($admin)
        ->get(route('admin.reports.farms.pdf'))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->actingAs($admin)
        ->get(route('admin.reports.orders.pdf', $order))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('farm pdf only contains the requested farm', function () {
    $admin = User::factory()->create();
    ['farmA' => $farmA, 'farmB' => $farmB] = seedOrderFixture();

    $response = $this->actingAs($admin)
        ->get(route('admin.reports.farms.show-pdf', $farmA));

    $response->assertOk();
    expect(strtolower((string) $response->headers->get('content-type')))->toContain('pdf');
    expect((string) $response->headers->get('content-disposition'))->toContain('feximar-finca-'.$farmA->id);
    expect((string) $response->headers->get('content-disposition'))->not->toContain('feximar-finca-'.$farmB->id);
});

test('order pdf only contains requested order id', function () {
    $admin = User::factory()->create();
    ['order' => $order] = seedOrderFixture();
    $other = Order::query()->create([
        'buyer_id' => $order->buyer_id,
        'status' => 'pending',
        'total' => 10,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.reports.orders.pdf', $order));

    $response->assertOk();
    expect(strtolower((string) $response->headers->get('content-type')))->toContain('pdf');
    expect((string) $response->headers->get('content-disposition'))->toContain('feximar-pedido-'.$order->id);
    expect((string) $response->headers->get('content-disposition'))->not->toContain('feximar-pedido-'.$other->id);
});

test('buyer cannot access admin reports or exports', function () {
    $buyerUser = User::factory()->create(['email' => 'buyer-report@test.local']);
    $buyer = Buyer::query()->create([
        'company_name' => 'Blocked Buyer',
        'contact_name' => 'X',
        'email' => 'blocked@test.local',
        'country' => 'United States',
        'active' => true,
    ]);
    BuyerUser::query()->create([
        'user_id' => $buyerUser->id,
        'buyer_id' => $buyer->id,
        'role' => 'buyer',
        'active' => true,
    ]);

    $this->actingAs($buyerUser)
        ->get(route('admin.reports.farms.pdf'))
        ->assertForbidden();

    $this->actingAs($buyerUser)
        ->get(route('admin.exports.orders'))
        ->assertForbidden();
});

test('farm user cannot download admin global report', function () {
    $farmUser = User::factory()->create(['email' => 'farm-report@test.local']);
    $farm = Farm::query()->create(['name' => 'Farm Portal', 'active' => true]);
    FarmUser::query()->create([
        'user_id' => $farmUser->id,
        'farm_id' => $farm->id,
        'role' => 'manager',
        'active' => true,
    ]);

    $this->actingAs($farmUser)
        ->get(route('admin.reports.farms.pdf'))
        ->assertForbidden();
});

test('export orders respects status filter', function () {
    $admin = User::factory()->create();
    ['order' => $order, 'buyer' => $buyer] = seedOrderFixture();
    Order::query()->create([
        'buyer_id' => $buyer->id,
        'status' => 'cancelled',
        'total' => 5,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.exports.orders', ['status' => 'confirmed']));

    $response->assertOk();
    $csv = $response->streamedContent();
    expect($csv)->toContain((string) $order->id);
    expect($csv)->toContain('confirmed');
    expect($csv)->not->toContain('cancelled');
});

test('bi filters modify sales result set', function () {
    $admin = User::factory()->create();
    ['farmA' => $farmA] = seedOrderFixture();

    $all = $this->actingAs($admin)->get(route('admin.bi.sales'));
    $all->assertOk();

    $filtered = $this->actingAs($admin)->get(route('admin.bi.sales', [
        'farm_id' => $farmA->id,
        'year' => 2026,
    ]));
    $filtered->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/BusinessIntelligence/Sales')
            ->where('filters.farm_id', $farmA->id));
});
