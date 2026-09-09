<?php

use App\Models\BoxType;
use App\Models\Buyer;
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
use App\Models\OrderDetail;
use App\Models\OrderFarmFulfillment;
use App\Models\PresentationBoxConfig;
use App\Models\Product;
use App\Models\User;
use App\Notifications\NewOrderForFarmNotification;
use App\Services\BuyerCartService;
use App\Services\BuyerCheckoutService;
use App\Services\OrderFulfillmentService;
use App\Support\FeximarMailBranding;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

function makePdfBuyerContext(string $suffix): array
{
    $user = User::factory()->create([
        'email' => "pdf-buyer-{$suffix}@test.local",
        'active' => true,
    ]);

    $buyer = Buyer::query()->create([
        'company_name' => "PDF Buyer {$suffix}",
        'contact_name' => "Contact {$suffix}",
        'email' => "pdf-co-{$suffix}@test.local",
        'country' => 'United States',
        'city' => 'Miami',
        'active' => true,
        'credit_allowed' => true,
        'credit_days_default' => 30,
    ]);

    BuyerUser::query()->create([
        'buyer_id' => $buyer->id,
        'user_id' => $user->id,
        'role' => 'manager',
        'active' => true,
    ]);

    return compact('user', 'buyer');
}

function makeFarmStockForNotify(string $suffix, ?string $farmEmail = null): array
{
    $farm = Farm::query()->create([
        'name' => "Notify Farm {$suffix}",
        'email' => $farmEmail,
        'active' => true,
    ]);

    $farmUser = User::factory()->create([
        'email' => "farm-user-{$suffix}@test.local",
        'active' => true,
    ]);

    FarmUser::query()->create([
        'farm_id' => $farm->id,
        'user_id' => $farmUser->id,
        'role' => 'manager',
        'active' => true,
    ]);

    $product = Product::query()->create([
        'name' => "Rose {$suffix}",
        'variety' => "Var {$suffix}",
        'color' => 'Blanco',
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
        'stems_per_bunch' => 25,
        'active' => true,
    ]);

    $box = BoxType::query()->firstOrCreate(
        ['code' => 'HB-NOTIFY'],
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
        'available_stems' => 5000,
        'reserved_stems' => 0,
        'price_per_stem' => 0.5,
        'active' => true,
    ]);

    return compact('farm', 'farmUser', 'product', 'presentation', 'box', 'availability');
}

function makeNotifyLogistics(): array
{
    $agency = CargoAgency::query()->firstOrCreate(
        ['code' => 'AG-NOTIFY'],
        ['name' => 'Agencia Notify', 'active' => true]
    );

    $country = Country::query()->firstOrCreate(
        ['iso2' => 'US'],
        [
            'name' => 'United States',
            'iso3' => 'USA',
            'phone_code' => '1',
            'active' => true,
        ]
    );

    return compact('agency', 'country');
}

test('buyer can download own order pdf', function () {
    $ctx = makePdfBuyerContext('own');
    $stock = makeFarmStockForNotify('pdf1', 'farm-pdf1@test.local');

    $order = Order::query()->create([
        'buyer_id' => $ctx['buyer']->id,
        'status' => 'pending',
        'total' => 100,
        'payment_condition' => 'cash',
        'notes' => 'PDF test',
    ]);

    OrderDetail::query()->create([
        'order_id' => $order->id,
        'farm_product_availability_id' => $stock['availability']->id,
        'box_type_id' => $stock['box']->id,
        'bunches' => 4,
        'stems_per_bunch' => 25,
        'boxes' => 1,
        'stems_per_box' => 200,
        'total_stems' => 100,
        'price_per_stem' => 0.5,
        'unit_price' => 0.5,
        'subtotal' => 50,
    ]);

    app(OrderFulfillmentService::class)->syncForOrder($order->fresh('details'));

    $response = $this->actingAs($ctx['user'])
        ->get(route('buyer.orders.pdf', $order));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

test('buyer cannot download foreign order pdf', function () {
    $owner = makePdfBuyerContext('owner');
    $intruder = makePdfBuyerContext('intruder');

    $order = Order::query()->create([
        'buyer_id' => $owner['buyer']->id,
        'status' => 'pending',
        'total' => 80,
        'payment_condition' => 'cash',
    ]);

    $this->actingAs($intruder['user'])
        ->get(route('buyer.orders.pdf', $order))
        ->assertForbidden();
});

test('confirmed checkout notifies each involved farm with only its lines', function () {
    Notification::fake();

    $ctx = makePdfBuyerContext('multi');
    $logistics = makeNotifyLogistics();
    $farmA = makeFarmStockForNotify('A', 'farm-a@notify.test');
    $farmB = makeFarmStockForNotify('B', 'farm-b@notify.test');

    $itemA = app(BuyerCartService::class)->addItem(
        $ctx['buyer'],
        $ctx['user'],
        $farmA['availability']->id,
        4
    );
    $itemB = app(BuyerCartService::class)->addItem(
        $ctx['buyer'],
        $ctx['user'],
        $farmB['availability']->id,
        8
    );

    $order = app(BuyerCheckoutService::class)->checkout($ctx['buyer'], $ctx['user'], [
        'payment_condition' => 'cash',
        'credit_days' => null,
        'cargo_agency_id' => $logistics['agency']->id,
        'shipping_method' => 'air',
        'destination_country_id' => $logistics['country']->id,
        'destination_city' => 'Miami',
        'destination_airport' => 'MIA',
        'destination_port' => null,
        'marking' => 'MARK-1',
        'packaging' => [
            ['cart_item_id' => $itemA->id, 'box_type_id' => $farmA['box']->id],
            ['cart_item_id' => $itemB->id, 'box_type_id' => $farmB['box']->id],
        ],
    ]);

    expect($order->farmFulfillments)->toHaveCount(2);
    Notification::assertSentOnDemandTimes(NewOrderForFarmNotification::class, 2);

    Notification::assertSentOnDemand(
        NewOrderForFarmNotification::class,
        function (NewOrderForFarmNotification $notification, array $channels, object $notifiable) use ($farmA, $order) {
            if (($notifiable->routes['mail'] ?? null) !== 'farm-a@notify.test') {
                return false;
            }

            expect($notification->farm->id)->toBe($farmA['farm']->id)
                ->and($notification->order->id)->toBe($order->id)
                ->and(collect($notification->lines))->toHaveCount(1)
                ->and(collect($notification->lines)->first()['product'])->toBe($farmA['product']->name);

            $mail = $notification->toMail($notifiable);
            expect($mail->subject)->toBe('Nuevo pedido recibido - FEXIMAR')
                ->and($mail->view)->toBe('emails.orders.new-for-farm')
                ->and($mail->viewData['brand'])->toBe('FEXIMAR')
                ->and($mail->viewData['logoUrl'])->toBe(FeximarMailBranding::logoAbsoluteUrl())
                ->and($mail->viewData['actionUrl'])->toContain('/farm/orders/')
                ->and($mail->viewData['actionUrl'])->toContain(rtrim((string) config('app.url'), '/'));

            $foreign = collect($notification->lines)->contains(
                fn ($line) => str_contains((string) $line['product'], 'Rose B')
            );
            expect($foreign)->toBeFalse();

            return true;
        }
    );

    Notification::assertSentOnDemand(
        NewOrderForFarmNotification::class,
        function (NewOrderForFarmNotification $notification, array $channels, object $notifiable) use ($farmB) {
            if (($notifiable->routes['mail'] ?? null) !== 'farm-b@notify.test') {
                return false;
            }

            expect($notification->farm->id)->toBe($farmB['farm']->id)
                ->and(collect($notification->lines))->toHaveCount(1)
                ->and(collect($notification->lines)->first()['product'])->toBe($farmB['product']->name);

            return true;
        }
    );
});

test('farm without commercial email uses primary farm user email', function () {
    Notification::fake();

    $ctx = makePdfBuyerContext('fallback');
    $logistics = makeNotifyLogistics();
    $farm = makeFarmStockForNotify('FB', null);

    $item = app(BuyerCartService::class)->addItem(
        $ctx['buyer'],
        $ctx['user'],
        $farm['availability']->id,
        2
    );

    app(BuyerCheckoutService::class)->checkout($ctx['buyer'], $ctx['user'], [
        'payment_condition' => 'cash',
        'credit_days' => null,
        'cargo_agency_id' => $logistics['agency']->id,
        'shipping_method' => 'air',
        'destination_country_id' => $logistics['country']->id,
        'destination_city' => 'Miami',
        'destination_airport' => 'MIA',
        'destination_port' => null,
        'marking' => null,
        'packaging' => [
            ['cart_item_id' => $item->id, 'box_type_id' => $farm['box']->id],
        ],
    ]);

    Notification::assertSentOnDemand(
        NewOrderForFarmNotification::class,
        fn (NewOrderForFarmNotification $notification, array $channels, object $notifiable) => ($notifiable->routes['mail'] ?? null) === $farm['farmUser']->email
    );
});

test('failed checkout does not send farm notification', function () {
    Notification::fake();

    $ctx = makePdfBuyerContext('fail');
    $logistics = makeNotifyLogistics();
    $farm = makeFarmStockForNotify('FAIL', 'farm-fail@notify.test');

    $item = app(BuyerCartService::class)->addItem(
        $ctx['buyer'],
        $ctx['user'],
        $farm['availability']->id,
        2
    );

    expect(fn () => app(BuyerCheckoutService::class)->checkout($ctx['buyer'], $ctx['user'], [
        'payment_condition' => 'cash',
        'credit_days' => null,
        'cargo_agency_id' => 999999,
        'shipping_method' => 'air',
        'destination_country_id' => $logistics['country']->id,
        'destination_city' => 'Miami',
        'destination_airport' => 'MIA',
        'destination_port' => null,
        'marking' => null,
        'packaging' => [
            ['cart_item_id' => $item->id, 'box_type_id' => $farm['box']->id],
        ],
    ]))->toThrow(\Illuminate\Validation\ValidationException::class);

    Notification::assertNothingSent();
});

test('feximar mail branding uses official logo asset and public absolute url', function () {
    expect(FeximarMailBranding::logoExists())->toBeTrue()
        ->and(FeximarMailBranding::logoAbsoluteUrl())->toContain('home/images/flores/logo-oscuro.png')
        ->and(FeximarMailBranding::logoAbsoluteUrl())->toContain(rtrim((string) config('app.url'), '/'))
        ->and(FeximarMailBranding::sharedViewData()['brand'])->toBe('FEXIMAR')
        ->and(FeximarMailBranding::sharedViewData()['tagline'])->toBe('Premium Flowers From Ecuador');
});
