<?php

use App\Models\BoxType;
use App\Models\Buyer;
use App\Models\Farm;
use App\Models\FarmProduct;
use App\Models\FarmProductAvailability;
use App\Models\FarmProductPresentation;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\User;
use App\Services\BusinessIntelligence\BusinessIntelligenceService;
use Carbon\Carbon;
use Illuminate\Http\Request;

test('admin can open bi executive dashboard', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.bi.executive'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/BusinessIntelligence/Executive')
            ->has('summary.kpis')
            ->has('filterOptions'));
});

test('bi service aggregates sales from order details without inventing data', function () {
    $farm = Farm::query()->create(['name' => 'BI Farm', 'active' => true]);
    $product = Product::query()->create(['name' => 'BI Product', 'active' => true, 'variety' => 'DemoVar']);
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
        'week_number' => 10,
        'available_stems' => 5000,
        'reserved_stems' => 0,
        'price_per_stem' => 0.45,
        'active' => true,
    ]);
    $box = BoxType::query()->create(['code' => 'HB', 'name' => 'HB', 'active' => true]);
    $buyer = Buyer::query()->create([
        'company_name' => 'BI Buyer',
        'contact_name' => 'Contact',
        'email' => 'bi-buyer@test.local',
        'country' => 'United States',
        'active' => true,
    ]);

    $createdAt = Carbon::create(2026, 3, 15, 12);
    $order = Order::query()->create([
        'buyer_id' => $buyer->id,
        'status' => 'confirmed',
        'total' => 360,
    ]);
    $order->forceFill([
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
    ])->saveQuietly();

    $detail = OrderDetail::query()->create([
        'order_id' => $order->id,
        'farm_product_availability_id' => $availability->id,
        'box_type_id' => $box->id,
        'boxes' => 2,
        'stems_per_box' => 200,
        'total_stems' => 400,
        'unit_price' => 180,
        'subtotal' => 360,
    ]);
    $detail->forceFill([
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
    ])->saveQuietly();

    $service = app(BusinessIntelligenceService::class);
    $request = Request::create('/admin/bi', 'GET', [
        'date_from' => '2026-03-01',
        'date_to' => '2026-03-31',
    ]);
    $filters = $service->filtersFromRequest($request);
    $kpis = collect($service->executiveKpis($filters)['kpis'])->keyBy('key');

    expect($kpis['sales']['value'])->toBe(360.0);
    expect($kpis['stems']['value'])->toBe(400);
    expect($kpis['boxes']['value'])->toBe(2);
    expect($kpis['orders']['value'])->toBe(1);

    $dataset = $service->predictionDataset(
        Carbon::create(2026, 1, 1),
        Carbon::create(2026, 12, 31)
    );
    expect($dataset->count())->toBeGreaterThan(0);
    expect($dataset->first())->toHaveProperties([
        'period',
        'variety',
        'farm',
        'stems_sold',
        'avg_stem_price',
        'orders_count',
    ]);
});

test('farm user cannot access bi module', function () {
    $user = User::factory()->create(['email' => 'bi-farm@test.local']);
    $farm = Farm::query()->create(['name' => 'Farm BI Block', 'active' => true]);
    \App\Models\FarmUser::query()->create([
        'farm_id' => $farm->id,
        'user_id' => $user->id,
        'role' => 'manager',
        'active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('admin.bi.executive'))
        ->assertForbidden();
});
