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
use App\Models\OrderFarmFulfillment;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderAcceptedNotification;
use App\Services\OrderFulfillmentService;
use Illuminate\Support\Facades\Notification;

function makePreparingNotificationContext(string $suffix = 'prep'): array
{
    $user = User::factory()->create(['email' => "farm-{$suffix}@test.local"]);
    $farm = Farm::query()->create([
        'name' => "Finca {$suffix}",
        'active' => true,
    ]);

    FarmUser::query()->create([
        'farm_id' => $farm->id,
        'user_id' => $user->id,
        'role' => 'manager',
        'active' => true,
    ]);

    $product = Product::query()->create([
        'name' => "Producto {$suffix}",
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

    $boxType = BoxType::query()->create([
        'code' => "BX{$suffix}",
        'name' => "Box {$suffix}",
        'active' => true,
    ]);

    $buyer = Buyer::query()->create([
        'company_name' => "Buyer {$suffix}",
        'contact_name' => "Contact {$suffix}",
        'email' => "buyer-{$suffix}@test.local",
        'country' => 'US',
        'active' => true,
    ]);

    $order = Order::query()->create([
        'buyer_id' => $buyer->id,
        'status' => 'pending',
        'total' => 450.50,
    ]);

    OrderDetail::query()->create([
        'order_id' => $order->id,
        'farm_product_availability_id' => $availability->id,
        'box_type_id' => $boxType->id,
        'boxes' => 2,
        'stems_per_box' => 200,
        'total_stems' => 400,
        'unit_price' => 225.25,
        'subtotal' => 450.50,
    ]);

    app(OrderFulfillmentService::class)->syncForOrder($order->fresh('details'));

    $fulfillment = OrderFarmFulfillment::query()
        ->where('order_id', $order->id)
        ->where('farm_id', $farm->id)
        ->firstOrFail();

    return compact('user', 'farm', 'availability', 'buyer', 'order', 'fulfillment');
}

test('farm marking order in preparation sends OrderAcceptedNotification to buyer email', function () {
    Notification::fake();

    $ctx = makePreparingNotificationContext('ok');

    $this->actingAs($ctx['user'])
        ->post(route('farm.orders.transition', $ctx['fulfillment']->id), [
            'status' => 'accepted',
        ])
        ->assertRedirect(route('farm.orders.show', $ctx['fulfillment']->id));

    expect($ctx['fulfillment']->fresh()->status)->toBe('accepted');
    expect($ctx['availability']->fresh()->reserved_stems)->toBe(400);
    Notification::assertNothingSent();

    $this->actingAs($ctx['user'])
        ->post(route('farm.orders.transition', $ctx['fulfillment']->id), [
            'status' => 'preparing',
        ])
        ->assertRedirect(route('farm.orders.show', $ctx['fulfillment']->id))
        ->assertSessionHas('success');

    expect($ctx['fulfillment']->fresh()->status)->toBe('preparing');
    expect($ctx['availability']->fresh()->reserved_stems)->toBe(400);

    Notification::assertSentOnDemand(
        OrderAcceptedNotification::class,
        function (OrderAcceptedNotification $notification, array $channels, object $notifiable) use ($ctx) {
            expect($channels)->toContain('mail')
                ->and($notifiable->routes['mail'] ?? null)->toBe($ctx['buyer']->email)
                ->and($notification->order->id)->toBe($ctx['order']->id)
                ->and($notification->farm->name)->toBe($ctx['farm']->name)
                ->and($notification->buyer->id)->toBe($ctx['buyer']->id);

            $mail = $notification->toMail($notifiable);

            expect($mail->subject)->toBe('Tu pedido está en preparación - FEXIMAR')
                ->and($mail->view)->toBe('emails.orders.accepted')
                ->and($mail->viewData['orderId'])->toBe($ctx['order']->id)
                ->and($mail->viewData['farmName'])->toBe($ctx['farm']->name)
                ->and($mail->viewData['brand'])->toBe('FEXIMAR')
                ->and($mail->viewData['logoUrl'])->toContain(rtrim((string) config('app.url'), '/'))
                ->and($mail->viewData['actionUrl'])->toContain(rtrim((string) config('app.url'), '/'));

            return true;
        }
    );
});

test('failed preparing transition does not send notification', function () {
    Notification::fake();

    $ctx = makePreparingNotificationContext('fail');

    $this->actingAs($ctx['user'])
        ->post(route('farm.orders.transition', $ctx['fulfillment']->id), [
            'status' => 'preparing',
        ])
        ->assertSessionHasErrors('status');

    expect($ctx['fulfillment']->fresh()->status)->toBe('pending');
    Notification::assertNothingSent();
});

test('duplicate preparing transition does not send notification again', function () {
    Notification::fake();

    $ctx = makePreparingNotificationContext('dup');

    $this->actingAs($ctx['user'])
        ->post(route('farm.orders.transition', $ctx['fulfillment']->id), ['status' => 'accepted'])
        ->assertRedirect();

    $this->actingAs($ctx['user'])
        ->post(route('farm.orders.transition', $ctx['fulfillment']->id), ['status' => 'preparing'])
        ->assertRedirect();

    Notification::assertSentOnDemandTimes(OrderAcceptedNotification::class, 1);

    $this->actingAs($ctx['user'])
        ->post(route('farm.orders.transition', $ctx['fulfillment']->id), ['status' => 'preparing'])
        ->assertSessionHasErrors('status');

    expect($ctx['fulfillment']->fresh()->status)->toBe('preparing');
    Notification::assertSentOnDemandTimes(OrderAcceptedNotification::class, 1);
});

test('wrong buyer does not receive preparing notification', function () {
    Notification::fake();

    $ctx = makePreparingNotificationContext('owner');
    $otherBuyer = Buyer::query()->create([
        'company_name' => 'Other Buyer',
        'contact_name' => 'Other Contact',
        'email' => 'other-buyer@test.local',
        'country' => 'US',
        'active' => true,
    ]);

    $this->actingAs($ctx['user'])
        ->post(route('farm.orders.transition', $ctx['fulfillment']->id), ['status' => 'accepted'])
        ->assertRedirect();

    $this->actingAs($ctx['user'])
        ->post(route('farm.orders.transition', $ctx['fulfillment']->id), ['status' => 'preparing'])
        ->assertRedirect();

    Notification::assertSentOnDemand(
        OrderAcceptedNotification::class,
        fn (OrderAcceptedNotification $notification, array $channels, object $notifiable) => ($notifiable->routes['mail'] ?? null) === $ctx['buyer']->email
            && ($notifiable->routes['mail'] ?? null) !== $otherBuyer->email
            && $notification->buyer->id === $ctx['buyer']->id
            && $notification->buyer->id !== $otherBuyer->id
    );
});
