<?php

use App\Models\BoxType;
use App\Models\Buyer;
use App\Models\Farm;
use App\Models\FarmProduct;
use App\Models\FarmProductAvailability;
use App\Models\FarmProductPresentation;
use App\Models\FarmUser;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\User;
use App\Services\BusinessIntelligence\BusinessIntelligenceService;
use Carbon\Carbon;
use Illuminate\Http\Request;

function makeBiFarmOrder(string $farmName, string $emailSuffix, float $subtotal = 360): array
{
    $user = User::factory()->create(['email' => "farm-{$emailSuffix}@test.local"]);
    $farm = Farm::query()->create(['name' => $farmName, 'active' => true]);

    FarmUser::query()->create([
        'farm_id' => $farm->id,
        'user_id' => $user->id,
        'role' => 'manager',
        'active' => true,
    ]);

    $product = Product::query()->create([
        'name' => "Product {$emailSuffix}",
        'variety' => "Var {$emailSuffix}",
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
        'week_number' => 12,
        'available_stems' => 5000,
        'reserved_stems' => 0,
        'price_per_stem' => 0.4,
        'active' => true,
    ]);
    $box = BoxType::query()->firstOrCreate(
        ['code' => 'HB-BI-'.$emailSuffix],
        ['name' => 'HB', 'active' => true]
    );
    $buyer = Buyer::query()->create([
        'company_name' => "Buyer {$emailSuffix}",
        'contact_name' => 'Contact',
        'email' => "buyer-{$emailSuffix}@test.local",
        'country' => 'United States',
        'active' => true,
    ]);

    $createdAt = Carbon::create(2026, 3, 15, 12);
    $order = Order::query()->create([
        'buyer_id' => $buyer->id,
        'status' => 'confirmed',
        'total' => $subtotal,
    ]);
    $order->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();

    $detail = OrderDetail::query()->create([
        'order_id' => $order->id,
        'farm_product_availability_id' => $availability->id,
        'box_type_id' => $box->id,
        'boxes' => 2,
        'stems_per_box' => 200,
        'total_stems' => 400,
        'unit_price' => $subtotal / 2,
        'subtotal' => $subtotal,
    ]);
    $detail->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();

    return compact('user', 'farm', 'order', 'availability');
}

test('farm bi requires farm user', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('farm.bi.executive'))
        ->assertForbidden();
});

test('farm user can open farm bi dashboard', function () {
    $ctx = makeBiFarmOrder('Farm BI Access', 'access');

    $this->actingAs($ctx['user'])
        ->get(route('farm.bi.executive'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Farm/BusinessIntelligence/Executive')
            ->where('farm.id', $ctx['farm']->id)
            ->missing('filters.farm_id')
            ->has('summary.kpis'));
});

test('farm A metrics exclude farm B order details', function () {
    $a = makeBiFarmOrder('Farm A BI', 'a', 360);
    $b = makeBiFarmOrder('Farm B BI', 'b', 900);

    $service = app(BusinessIntelligenceService::class);

    $filtersA = $service->filtersForFarm(
        Request::create('/farm/bi', 'GET', [
            'date_from' => '2026-03-01',
            'date_to' => '2026-03-31',
            'farm_id' => $b['farm']->id, // intento de manipulación
        ]),
        $a['farm']->id
    );

    expect($filtersA['farm_id'])->toBe($a['farm']->id);

    $kpis = collect($service->farmExecutiveKpis($filtersA)['kpis'])->keyBy('key');
    expect($kpis['sales']['value'])->toBe(360.0);
    expect($kpis['sales']['value'])->not->toBe(900.0);
});

test('farm A cannot see farm B bi pages by query param', function () {
    $a = makeBiFarmOrder('Farm A Iso', 'iso-a', 100);
    $b = makeBiFarmOrder('Farm B Iso', 'iso-b', 500);

    $this->actingAs($a['user'])
        ->get(route('farm.bi.sales', ['farm_id' => $b['farm']->id, 'date_from' => '2026-03-01', 'date_to' => '2026-03-31']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Farm/BusinessIntelligence/Sales')
            ->where('farm.id', $a['farm']->id));
});

test('farm bi filters force authenticated farm_id', function () {
    $a = makeBiFarmOrder('Farm Filter A', 'filter-a', 200);
    $b = makeBiFarmOrder('Farm Filter B', 'filter-b', 800);

    $service = app(BusinessIntelligenceService::class);
    $filters = $service->filtersForFarm(
        Request::create('/farm/bi/sales', 'GET', [
            'farm_id' => $b['farm']->id,
            'date_from' => '2026-03-01',
            'date_to' => '2026-03-31',
        ]),
        $a['farm']->id
    );

    $table = $service->farmHistoricalTable($filters);
    expect($filters['farm_id'])->toBe($a['farm']->id);
    expect(collect($table)->sum('sales'))->toBe(200.0);
});
