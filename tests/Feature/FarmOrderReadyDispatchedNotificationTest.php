<?php

use App\Models\BoxType;
use App\Models\Buyer;
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
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderDispatchedNotification;
use App\Notifications\OrderReadyNotification;
use App\Services\OrderFulfillmentService;
use Illuminate\Support\Facades\Notification;

function makeReadyDispatchedContext(string $suffix, bool $withLogistics = true): array
{
    $user = User::factory()->create(['email' => "farm-rd-{$suffix}@test.local"]);
    $farm = Farm::query()->create([
        'name' => "Finca RD {$suffix}",
        'active' => true,
    ]);

    FarmUser::query()->create([
        'farm_id' => $farm->id,
        'user_id' => $user->id,
        'role' => 'manager',
        'active' => true,
    ]);

    $product = Product::query()->create([
        'name' => "Producto RD {$suffix}",
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
        'week_number' => 38,
        'available_stems' => 5000,
        'reserved_stems' => 0,
        'price_per_stem' => 0.50,
        'active' => true,
    ]);

    $boxType = BoxType::query()->create([
        'code' => "RD{$suffix}",
        'name' => "Box RD {$suffix}",
        'active' => true,
    ]);

    $buyer = Buyer::query()->create([
        'company_name' => "Buyer RD {$suffix}",
        'contact_name' => "Contact RD {$suffix}",
        'email' => "buyer-rd-{$suffix}@test.local",
        'country' => 'US',
        'active' => true,
    ]);

    $orderPayload = [
        'buyer_id' => $buyer->id,
        'status' => 'pending',
        'total' => 200.00,
    ];

    if ($withLogistics) {
        $agency = CargoAgency::query()->create([
            'name' => "Agency RD {$suffix}",
            'code' => "AG{$suffix}",
            'active' => true,
        ]);

        $country = Country::query()->firstOrCreate(
            ['iso2' => 'US'],
            ['name' => 'United States', 'iso3' => 'USA', 'active' => true]
        );

        $orderPayload = array_merge($orderPayload, [
            'cargo_agency_id' => $agency->id,
            'shipping_method' => 'air',
            'destination_country_id' => $country->id,
            'destination_city' => 'Miami',
            'destination_airport' => 'MIA',
        ]);
    }

    $order = Order::query()->create($orderPayload);

    OrderDetail::query()->create([
        'order_id' => $order->id,
        'farm_product_availability_id' => $availability->id,
        'box_type_id' => $boxType->id,
        'boxes' => 1,
        'stems_per_box' => 100,
        'total_stems' => 100,
        'unit_price' => 200.00,
        'subtotal' => 200.00,
    ]);

    app(OrderFulfillmentService::class)->syncForOrder($order->fresh('details'));

    $fulfillment = OrderFarmFulfillment::query()
        ->where('order_id', $order->id)
        ->where('farm_id', $farm->id)
        ->firstOrFail();

    return compact('user', 'farm', 'availability', 'buyer', 'order', 'fulfillment');
}

function advanceFulfillmentTo(array $ctx, string $targetStatus): void
{
    $path = match ($targetStatus) {
        'accepted' => ['accepted'],
        'preparing' => ['accepted', 'preparing'],
        'ready' => ['accepted', 'preparing', 'ready'],
        'dispatched' => ['accepted', 'preparing', 'ready', 'dispatched'],
        default => throw new \InvalidArgumentException("Unsupported target status: {$targetStatus}"),
    };

    foreach ($path as $status) {
        test()->actingAs($ctx['user'])
            ->post(route('farm.orders.transition', $ctx['fulfillment']->id), [
                'status' => $status,
            ])
            ->assertRedirect(route('farm.orders.show', $ctx['fulfillment']->id));
    }
}

test('transition to ready sends OrderReadyNotification to buyer email', function () {
    Notification::fake();

    $ctx = makeReadyDispatchedContext('ready-ok');
    advanceFulfillmentTo($ctx, 'preparing');
    Notification::fake();

    $this->actingAs($ctx['user'])
        ->post(route('farm.orders.transition', $ctx['fulfillment']->id), [
            'status' => 'ready',
        ])
        ->assertRedirect(route('farm.orders.show', $ctx['fulfillment']->id))
        ->assertSessionHas('success');

    expect($ctx['fulfillment']->fresh()->status)->toBe('ready');

    Notification::assertSentOnDemand(
        OrderReadyNotification::class,
        function (OrderReadyNotification $notification, array $channels, object $notifiable) use ($ctx) {
            expect($channels)->toContain('mail')
                ->and($notifiable->routes['mail'] ?? null)->toBe($ctx['buyer']->email)
                ->and($notification->order->id)->toBe($ctx['order']->id)
                ->and($notification->farm->name)->toBe($ctx['farm']->name)
                ->and($notification->buyer->id)->toBe($ctx['buyer']->id);

            $mail = $notification->toMail($notifiable);

            expect($mail->subject)->toBe('Tu pedido está listo - FEXIMAR')
                ->and($mail->view)->toBe('emails.orders.ready')
                ->and($mail->viewData['orderId'])->toBe($ctx['order']->id)
                ->and($mail->viewData['farmName'])->toBe($ctx['farm']->name)
                ->and($mail->viewData['buyerCompany'])->toBe($ctx['buyer']->company_name)
                ->and($mail->viewData['brand'])->toBe('FEXIMAR')
                ->and($mail->viewData['logoUrl'])->toContain(rtrim((string) config('app.url'), '/'))
                ->and($mail->viewData['actionUrl'])->toContain(rtrim((string) config('app.url'), '/'));

            return true;
        }
    );
});

test('failed ready transition does not send notification', function () {
    Notification::fake();

    $ctx = makeReadyDispatchedContext('ready-fail');

    $this->actingAs($ctx['user'])
        ->post(route('farm.orders.transition', $ctx['fulfillment']->id), [
            'status' => 'ready',
        ])
        ->assertSessionHasErrors('status');

    expect($ctx['fulfillment']->fresh()->status)->toBe('pending');
    Notification::assertNothingSent();
});

test('duplicate ready transition does not resend notification', function () {
    Notification::fake();

    $ctx = makeReadyDispatchedContext('ready-dup');
    advanceFulfillmentTo($ctx, 'preparing');
    Notification::fake();

    $this->actingAs($ctx['user'])
        ->post(route('farm.orders.transition', $ctx['fulfillment']->id), ['status' => 'ready'])
        ->assertRedirect();

    Notification::assertSentOnDemandTimes(OrderReadyNotification::class, 1);

    $this->actingAs($ctx['user'])
        ->post(route('farm.orders.transition', $ctx['fulfillment']->id), ['status' => 'ready'])
        ->assertSessionHasErrors('status');

    expect($ctx['fulfillment']->fresh()->status)->toBe('ready');
    Notification::assertSentOnDemandTimes(OrderReadyNotification::class, 1);
});

test('wrong buyer does not receive ready notification', function () {
    Notification::fake();

    $ctx = makeReadyDispatchedContext('ready-owner');
    $otherBuyer = Buyer::query()->create([
        'company_name' => 'Other Ready Buyer',
        'contact_name' => 'Other Ready Contact',
        'email' => 'other-ready-buyer@test.local',
        'country' => 'US',
        'active' => true,
    ]);

    advanceFulfillmentTo($ctx, 'preparing');
    Notification::fake();

    $this->actingAs($ctx['user'])
        ->post(route('farm.orders.transition', $ctx['fulfillment']->id), ['status' => 'ready'])
        ->assertRedirect();

    Notification::assertSentOnDemand(
        OrderReadyNotification::class,
        fn (OrderReadyNotification $notification, array $channels, object $notifiable) => ($notifiable->routes['mail'] ?? null) === $ctx['buyer']->email
            && ($notifiable->routes['mail'] ?? null) !== $otherBuyer->email
            && $notification->buyer->id === $ctx['buyer']->id
            && $notification->buyer->id !== $otherBuyer->id
    );
});

test('transition to dispatched sends OrderDispatchedNotification with logistics', function () {
    Notification::fake();

    $ctx = makeReadyDispatchedContext('disp-ok', withLogistics: true);
    advanceFulfillmentTo($ctx, 'ready');
    Notification::fake();

    $this->actingAs($ctx['user'])
        ->post(route('farm.orders.transition', $ctx['fulfillment']->id), [
            'status' => 'dispatched',
        ])
        ->assertRedirect(route('farm.orders.show', $ctx['fulfillment']->id))
        ->assertSessionHas('success');

    expect($ctx['fulfillment']->fresh()->status)->toBe('dispatched');

    Notification::assertSentOnDemand(
        OrderDispatchedNotification::class,
        function (OrderDispatchedNotification $notification, array $channels, object $notifiable) use ($ctx) {
            expect($channels)->toContain('mail')
                ->and($notifiable->routes['mail'] ?? null)->toBe($ctx['buyer']->email)
                ->and($notification->order->id)->toBe($ctx['order']->id)
                ->and($notification->farm->name)->toBe($ctx['farm']->name);

            $mail = $notification->toMail($notifiable);

            expect($mail->subject)->toBe('Tu pedido ha sido despachado - FEXIMAR')
                ->and($mail->view)->toBe('emails.orders.dispatched')
                ->and($mail->viewData['orderId'])->toBe($ctx['order']->id)
                ->and($mail->viewData['farmName'])->toBe($ctx['farm']->name)
                ->and($mail->viewData['cargoAgency'])->toBe('Agency RD disp-ok')
                ->and($mail->viewData['shippingMethod'])->toBe('Aéreo')
                ->and($mail->viewData['destination'])->toContain('Miami')
                ->and($mail->viewData['airportOrPort'])->toBe('MIA')
                ->and($mail->viewData['brand'])->toBe('FEXIMAR')
                ->and($mail->viewData['logoUrl'])->toContain(rtrim((string) config('app.url'), '/'))
                ->and($mail->viewData['actionUrl'])->toContain(rtrim((string) config('app.url'), '/'));

            return true;
        }
    );
});

test('dispatched notification omits missing logistics fields', function () {
    Notification::fake();

    $ctx = makeReadyDispatchedContext('disp-nolog', withLogistics: false);
    advanceFulfillmentTo($ctx, 'ready');
    Notification::fake();

    $this->actingAs($ctx['user'])
        ->post(route('farm.orders.transition', $ctx['fulfillment']->id), ['status' => 'dispatched'])
        ->assertRedirect();

    Notification::assertSentOnDemand(
        OrderDispatchedNotification::class,
        function (OrderDispatchedNotification $notification, array $channels, object $notifiable) {
            $mail = $notification->toMail($notifiable);

            expect($mail->viewData['cargoAgency'])->toBeNull()
                ->and($mail->viewData['shippingMethod'])->toBeNull()
                ->and($mail->viewData['destination'])->toBeNull()
                ->and($mail->viewData['airportOrPort'])->toBeNull();

            return true;
        }
    );
});

test('failed dispatched transition does not send notification', function () {
    Notification::fake();

    $ctx = makeReadyDispatchedContext('disp-fail');

    $this->actingAs($ctx['user'])
        ->post(route('farm.orders.transition', $ctx['fulfillment']->id), [
            'status' => 'dispatched',
        ])
        ->assertSessionHasErrors('status');

    expect($ctx['fulfillment']->fresh()->status)->toBe('pending');
    Notification::assertNothingSent();
});

test('duplicate dispatched transition does not resend notification', function () {
    Notification::fake();

    $ctx = makeReadyDispatchedContext('disp-dup');
    advanceFulfillmentTo($ctx, 'ready');
    Notification::fake();

    $this->actingAs($ctx['user'])
        ->post(route('farm.orders.transition', $ctx['fulfillment']->id), ['status' => 'dispatched'])
        ->assertRedirect();

    Notification::assertSentOnDemandTimes(OrderDispatchedNotification::class, 1);

    $this->actingAs($ctx['user'])
        ->post(route('farm.orders.transition', $ctx['fulfillment']->id), ['status' => 'dispatched'])
        ->assertSessionHasErrors('status');

    expect($ctx['fulfillment']->fresh()->status)->toBe('dispatched');
    Notification::assertSentOnDemandTimes(OrderDispatchedNotification::class, 1);
});

test('wrong buyer does not receive dispatched notification', function () {
    Notification::fake();

    $ctx = makeReadyDispatchedContext('disp-owner');
    $otherBuyer = Buyer::query()->create([
        'company_name' => 'Other Disp Buyer',
        'contact_name' => 'Other Disp Contact',
        'email' => 'other-disp-buyer@test.local',
        'country' => 'US',
        'active' => true,
    ]);

    advanceFulfillmentTo($ctx, 'ready');
    Notification::fake();

    $this->actingAs($ctx['user'])
        ->post(route('farm.orders.transition', $ctx['fulfillment']->id), ['status' => 'dispatched'])
        ->assertRedirect();

    Notification::assertSentOnDemand(
        OrderDispatchedNotification::class,
        fn (OrderDispatchedNotification $notification, array $channels, object $notifiable) => ($notifiable->routes['mail'] ?? null) === $ctx['buyer']->email
            && ($notifiable->routes['mail'] ?? null) !== $otherBuyer->email
            && $notification->buyer->id === $ctx['buyer']->id
    );
});

test('multi-farm ready notification identifies only the transitioning farm', function () {
    Notification::fake();

    $buyer = Buyer::query()->create([
        'company_name' => 'Multi Farm Buyer',
        'contact_name' => 'MF Contact',
        'email' => 'mf-ready-buyer@test.local',
        'country' => 'US',
        'active' => true,
    ]);

    $makeFarm = function (string $suffix) {
        $user = User::factory()->create(['email' => "farm-mf-{$suffix}@test.local"]);
        $farm = Farm::query()->create([
            'name' => "Multi Farm {$suffix}",
            'active' => true,
        ]);
        FarmUser::query()->create([
            'farm_id' => $farm->id,
            'user_id' => $user->id,
            'role' => 'manager',
            'active' => true,
        ]);

        $product = Product::query()->create([
            'name' => "MF Product {$suffix}",
            'active' => true,
        ]);
        $farmProduct = FarmProduct::query()->create([
            'farm_id' => $farm->id,
            'product_id' => $product->id,
            'active' => true,
        ]);
        $presentation = FarmProductPresentation::query()->create([
            'farm_product_id' => $farmProduct->id,
            'stem_length_cm' => 40,
            'active' => true,
        ]);
        $availability = FarmProductAvailability::query()->create([
            'farm_product_presentation_id' => $presentation->id,
            'year' => 2026,
            'week_number' => 39,
            'available_stems' => 3000,
            'reserved_stems' => 0,
            'price_per_stem' => 0.40,
            'active' => true,
        ]);

        return compact('user', 'farm', 'availability');
    };

    $farmA = $makeFarm('A');
    $farmB = $makeFarm('B');

    $boxType = BoxType::query()->create([
        'code' => 'MFREADY',
        'name' => 'MF Ready Box',
        'active' => true,
    ]);

    $order = Order::query()->create([
        'buyer_id' => $buyer->id,
        'status' => 'pending',
        'total' => 300.00,
    ]);

    OrderDetail::query()->create([
        'order_id' => $order->id,
        'farm_product_availability_id' => $farmA['availability']->id,
        'box_type_id' => $boxType->id,
        'boxes' => 1,
        'stems_per_box' => 100,
        'total_stems' => 100,
        'unit_price' => 100.00,
        'subtotal' => 100.00,
    ]);

    OrderDetail::query()->create([
        'order_id' => $order->id,
        'farm_product_availability_id' => $farmB['availability']->id,
        'box_type_id' => $boxType->id,
        'boxes' => 1,
        'stems_per_box' => 200,
        'total_stems' => 200,
        'unit_price' => 200.00,
        'subtotal' => 200.00,
    ]);

    app(OrderFulfillmentService::class)->syncForOrder($order->fresh('details'));

    $fulfillmentA = OrderFarmFulfillment::query()
        ->where('order_id', $order->id)
        ->where('farm_id', $farmA['farm']->id)
        ->firstOrFail();

    $fulfillmentB = OrderFarmFulfillment::query()
        ->where('order_id', $order->id)
        ->where('farm_id', $farmB['farm']->id)
        ->firstOrFail();

    foreach (['accepted', 'preparing'] as $status) {
        $this->actingAs($farmA['user'])
            ->post(route('farm.orders.transition', $fulfillmentA->id), ['status' => $status])
            ->assertRedirect();
    }

    Notification::fake();

    $this->actingAs($farmA['user'])
        ->post(route('farm.orders.transition', $fulfillmentA->id), ['status' => 'ready'])
        ->assertRedirect();

    Notification::assertSentOnDemandTimes(OrderReadyNotification::class, 1);

    Notification::assertSentOnDemand(
        OrderReadyNotification::class,
        function (OrderReadyNotification $notification) use ($farmA, $farmB, $order, $buyer) {
            expect($notification->farm->id)->toBe($farmA['farm']->id)
                ->and($notification->farm->name)->toBe($farmA['farm']->name)
                ->and($notification->farm->id)->not->toBe($farmB['farm']->id)
                ->and($notification->order->id)->toBe($order->id)
                ->and($notification->buyer->id)->toBe($buyer->id)
                ->and($notification->farmTotal)->toBe(100.0);

            $mail = $notification->toMail((object) ['routes' => ['mail' => $buyer->email]]);

            expect($mail->viewData['farmName'])->toBe($farmA['farm']->name)
                ->and($mail->viewData['farmName'])->not->toBe($farmB['farm']->name);

            return true;
        }
    );

    expect($fulfillmentA->fresh()->status)->toBe('ready');
    expect($fulfillmentB->fresh()->status)->toBe('pending');
});
