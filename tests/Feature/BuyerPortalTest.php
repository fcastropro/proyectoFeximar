<?php

use App\Models\BoxType;
use App\Models\Buyer;
use App\Models\BuyerCart;
use App\Models\BuyerCartItem;
use App\Models\BuyerUser;
use App\Models\CargoAgency;
use App\Models\Country;
use App\Models\Farm;
use App\Models\FarmProduct;
use App\Models\FarmProductAvailability;
use App\Models\FarmProductPresentation;
use App\Models\FarmUser;
use App\Models\Order;
use App\Models\PresentationBoxConfig;
use App\Models\Product;
use App\Models\User;
use App\Services\BuyerCartService;
use App\Services\BuyerCheckoutService;
use App\Services\FarmAvailabilityReservationService;
use Carbon\Carbon;

function makeBuyerContext(string $suffix, bool $credit = true, int $creditDays = 30): array
{
    $user = User::factory()->create([
        'email' => "buyer-{$suffix}@test.local",
        'name' => "Buyer User {$suffix}",
        'password' => bcrypt('password'),
    ]);

    $buyer = Buyer::query()->create([
        'company_name' => "Buyer Co {$suffix}",
        'contact_name' => "Contact {$suffix}",
        'email' => "co-{$suffix}@test.local",
        'country' => 'United States',
        'active' => true,
        'credit_allowed' => $credit,
        'credit_days_default' => $credit ? $creditDays : null,
    ]);

    BuyerUser::query()->create([
        'buyer_id' => $buyer->id,
        'user_id' => $user->id,
        'role' => 'manager',
        'active' => true,
    ]);

    return compact('user', 'buyer');
}

function makeBuyerCatalogStock(int $available = 2000, int $reserved = 0, float $pricePerStem = 0.45, int $stemsPerBunch = 25): array
{
    $farm = Farm::query()->create(['name' => 'Buyer Stock Farm '.uniqid(), 'active' => true]);
    $product = Product::query()->create([
        'name' => 'Buyer Catalog Rose',
        'variety' => 'Freedom',
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
        'stems_per_bunch' => $stemsPerBunch,
        'active' => true,
    ]);

    $box = BoxType::query()->firstOrCreate(
        ['code' => 'HB-BUYER'],
        ['name' => 'HB', 'active' => true]
    );

    PresentationBoxConfig::query()->create([
        'farm_product_presentation_id' => $presentation->id,
        'box_type_id' => $box->id,
        'stems_per_box' => 200,
        'bunches_per_box' => 8,
        'active' => true,
    ]);

    $now = Carbon::now();
    $availability = FarmProductAvailability::query()->create([
        'farm_product_presentation_id' => $presentation->id,
        'year' => (int) $now->isoWeekYear(),
        'week_number' => (int) $now->isoWeek(),
        'available_stems' => $available,
        'reserved_stems' => $reserved,
        'price_per_stem' => $pricePerStem,
        'active' => true,
    ]);

    $agency = CargoAgency::query()->firstOrCreate(
        ['code' => 'AG1'],
        ['name' => 'Agencia de carga 1', 'active' => true]
    );

    $country = Country::query()->orderBy('id')->first()
        ?? Country::query()->create([
            'name' => 'United States',
            'iso2' => 'US',
            'iso3' => 'USA',
            'active' => true,
        ]);

    return compact('farm', 'product', 'presentation', 'box', 'availability', 'agency', 'country');
}

function checkoutPayload(array $ctx, array $stock, BuyerCartItem $item, array $overrides = []): array
{
    return array_merge([
        'payment_condition' => 'cash',
        'credit_days' => null,
        'cargo_agency_id' => $stock['agency']->id,
        'shipping_method' => 'air',
        'destination_country_id' => $stock['country']->id,
        'destination_city' => 'Miami',
        'destination_airport' => 'MIA',
        'destination_port' => null,
        'marking' => "SUNSHINE FLOWERS\nPO-45872",
        'packaging' => [
            [
                'cart_item_id' => $item->id,
                'box_type_id' => $stock['box']->id,
            ],
        ],
    ], $overrides);
}

test('buyer cannot access admin or farm portals', function () {
    $ctx = makeBuyerContext('iso');

    $this->actingAs($ctx['user'])->get('/admin')->assertForbidden();
    $this->actingAs($ctx['user'])->get(route('farm.dashboard'))->assertForbidden();
});

test('farm user cannot access buyer portal', function () {
    $user = User::factory()->create(['email' => 'farm-no-buyer@test.local']);
    $farm = Farm::query()->create(['name' => 'No Buyer Farm', 'active' => true]);
    FarmUser::query()->create([
        'farm_id' => $farm->id,
        'user_id' => $user->id,
        'role' => 'manager',
        'active' => true,
    ]);

    $this->actingAs($user)->get(route('buyer.dashboard'))->assertForbidden();
});

test('buyer A cannot view buyer B orders', function () {
    $a = makeBuyerContext('a');
    $b = makeBuyerContext('b');

    $order = Order::query()->create([
        'buyer_id' => $b['buyer']->id,
        'status' => 'pending',
        'total' => 100,
        'payment_condition' => 'cash',
    ]);

    $this->actingAs($a['user'])
        ->get(route('buyer.orders.show', $order))
        ->assertForbidden();
});

test('catalog hides zero effective availability', function () {
    $ctx = makeBuyerContext('cat');
    $stock = makeBuyerCatalogStock(100, 100);

    $this->actingAs($ctx['user'])
        ->get(route('buyer.catalog.index', [
            'year' => $stock['availability']->year,
            'week' => $stock['availability']->week_number,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Buyer/Catalog/Index')
            ->where('items', fn ($items) => collect($items)->every(
                fn ($item) => $item['availability_id'] !== $stock['availability']->id
            )));
});

test('purchase by bunches calculates stems and subtotal on backend', function () {
    $ctx = makeBuyerContext('price');
    $stock = makeBuyerCatalogStock(2000, 0, 0.45, 25);

    $service = app(BuyerCartService::class);
    $item = $service->addItem(
        $ctx['buyer'],
        $ctx['user'],
        $stock['availability']->id,
        10
    );

    expect((int) $item->bunches)->toBe(10)
        ->and((int) $item->stems_per_bunch_snapshot)->toBe(25)
        ->and((int) $item->total_stems)->toBe(250)
        ->and((float) $item->price_per_stem_snapshot)->toBe(0.45)
        ->and((float) $item->subtotal)->toBe(112.5);
});

test('buyer cannot exceed availability with bunches', function () {
    $ctx = makeBuyerContext('over');
    $stock = makeBuyerCatalogStock(200, 0, 0.4, 25); // max 8 bunches

    expect(fn () => app(BuyerCartService::class)->addItem(
        $ctx['buyer'],
        $ctx['user'],
        $stock['availability']->id,
        10
    ))->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('checkout requires agency shipping and saves marking packaging', function () {
    $ctx = makeBuyerContext('chk');
    $stock = makeBuyerCatalogStock(2000, 0, 0.45, 25);

    $item = app(BuyerCartService::class)->addItem(
        $ctx['buyer'],
        $ctx['user'],
        $stock['availability']->id,
        10
    );

    expect(fn () => app(BuyerCheckoutService::class)->checkout(
        $ctx['buyer'],
        $ctx['user'],
        checkoutPayload($ctx, $stock, $item, ['cargo_agency_id' => 999999])
    ))->toThrow(\Illuminate\Validation\ValidationException::class);

    $order = app(BuyerCheckoutService::class)->checkout(
        $ctx['buyer'],
        $ctx['user'],
        checkoutPayload($ctx, $stock, $item)
    );

    expect($order->cargo_agency_id)->toBe($stock['agency']->id)
        ->and($order->shipping_method)->toBe('air')
        ->and($order->destination_airport)->toBe('MIA')
        ->and($order->marking)->toContain('PO-45872')
        ->and($order->details)->toHaveCount(1)
        ->and((int) $order->details->first()->bunches)->toBe(10)
        ->and((int) $order->details->first()->stems_per_bunch)->toBe(25)
        ->and((int) $order->details->first()->total_stems)->toBe(250)
        ->and((int) $order->details->first()->boxes)->toBe(2) // ceil(250/200)
        ->and($order->farmFulfillments)->toHaveCount(1);

    $stock['availability']->refresh();
    expect((int) $stock['availability']->reserved_stems)->toBe(0);
});

test('air requires airport and sea requires port', function () {
    $ctx = makeBuyerContext('ship');
    $stock = makeBuyerCatalogStock();
    $item = app(BuyerCartService::class)->addItem(
        $ctx['buyer'],
        $ctx['user'],
        $stock['availability']->id,
        2
    );

    expect(fn () => app(BuyerCheckoutService::class)->checkout(
        $ctx['buyer'],
        $ctx['user'],
        checkoutPayload($ctx, $stock, $item, [
            'shipping_method' => 'air',
            'destination_airport' => null,
        ])
    ))->toThrow(\Illuminate\Validation\ValidationException::class);

    $item2 = app(BuyerCartService::class)->addItem(
        $ctx['buyer'],
        $ctx['user'],
        $stock['availability']->id,
        2
    );

    expect(fn () => app(BuyerCheckoutService::class)->checkout(
        $ctx['buyer'],
        $ctx['user'],
        checkoutPayload($ctx, $stock, $item2, [
            'shipping_method' => 'sea',
            'destination_airport' => null,
            'destination_port' => null,
        ])
    ))->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('farm reservation still uses total_stems after bunch checkout', function () {
    $ctx = makeBuyerContext('res');
    $stock = makeBuyerCatalogStock(2000, 0, 0.45, 25);

    $item = app(BuyerCartService::class)->addItem(
        $ctx['buyer'],
        $ctx['user'],
        $stock['availability']->id,
        10
    );

    $order = app(BuyerCheckoutService::class)->checkout(
        $ctx['buyer'],
        $ctx['user'],
        checkoutPayload($ctx, $stock, $item)
    );

    $fulfillment = $order->farmFulfillments->first();
    app(FarmAvailabilityReservationService::class)->reserveOnAccept($fulfillment);

    $stock['availability']->refresh();
    expect((int) $stock['availability']->reserved_stems)->toBe(250);
});

test('credit still restricted without authorization', function () {
    $ctx = makeBuyerContext('nocredit', false);
    $stock = makeBuyerCatalogStock();
    $item = app(BuyerCartService::class)->addItem(
        $ctx['buyer'],
        $ctx['user'],
        $stock['availability']->id,
        1
    );

    expect(fn () => app(BuyerCheckoutService::class)->checkout(
        $ctx['buyer'],
        $ctx['user'],
        checkoutPayload($ctx, $stock, $item, [
            'payment_condition' => 'credit',
            'credit_days' => 30,
        ])
    ))->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('buyer login redirects to buyer dashboard', function () {
    $ctx = makeBuyerContext('login');

    $this->post('/login', [
        'email' => $ctx['user']->email,
        'password' => 'password',
    ])->assertRedirect(route('buyer.dashboard', absolute: false));
});
