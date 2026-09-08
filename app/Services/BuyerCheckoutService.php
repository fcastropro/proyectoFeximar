<?php

namespace App\Services;

use App\Models\Buyer;
use App\Models\BuyerCart;
use App\Models\CargoAgency;
use App\Models\Country;
use App\Models\FarmProductAvailability;
use App\Models\Order;
use App\Models\User;
use App\Services\Admin\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BuyerCheckoutService
{
    public function __construct(
        private readonly BuyerCartService $cartService,
        private readonly OrderFulfillmentService $fulfillmentService,
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * @param  array{
     *   payment_condition:string,
     *   credit_days:?int,
     *   cargo_agency_id:int,
     *   shipping_method:string,
     *   destination_country_id:int,
     *   destination_city:?string,
     *   destination_airport:?string,
     *   destination_port:?string,
     *   marking:?string,
     *   packaging:array<int,array{cart_item_id:int,box_type_id:int}>
     * }  $payload
     */
    public function checkout(Buyer $buyer, User $user, array $payload): Order
    {
        $this->assertPayment($buyer, $payload['payment_condition'], $payload['credit_days'] ?? null);
        $this->assertLogistics($payload);

        return DB::transaction(function () use ($buyer, $user, $payload) {
            $cart = $this->cartService->activeCart($buyer, $user);
            $cart->load('items');

            if ($cart->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => 'El carrito está vacío.',
                ]);
            }

            $packagingByItem = collect($payload['packaging'])
                ->keyBy(fn ($row) => (int) $row['cart_item_id']);

            if ($packagingByItem->count() !== $cart->items->count()) {
                throw ValidationException::withMessages([
                    'packaging' => 'Debes seleccionar embalaje para cada línea del pedido.',
                ]);
            }

            $details = [];
            $total = 0.0;
            $stemsByAvailability = [];

            foreach ($cart->items as $item) {
                $pack = $packagingByItem->get($item->id);
                if (! $pack) {
                    throw ValidationException::withMessages([
                        'packaging' => 'Falta embalaje para una línea del carrito.',
                    ]);
                }

                $availability = FarmProductAvailability::query()
                    ->with('presentation')
                    ->whereKey($item->farm_product_availability_id)
                    ->where('active', true)
                    ->lockForUpdate()
                    ->first();

                if (! $availability) {
                    throw ValidationException::withMessages([
                        'cart' => 'Una disponibilidad del carrito ya no está activa.',
                    ]);
                }

                $priced = $this->cartService->priceLine($availability, (int) $item->bunches);
                $packEst = $this->cartService->estimatePackaging(
                    $availability,
                    (int) $pack['box_type_id'],
                    $priced['total_stems'],
                    $priced['bunches']
                );

                $stemsByAvailability[$availability->id] = ($stemsByAvailability[$availability->id] ?? 0)
                    + $priced['total_stems'];

                if ($stemsByAvailability[$availability->id] > $availability->remainingStems()) {
                    throw ValidationException::withMessages([
                        'cart' => 'La disponibilidad efectiva cambió. Actualiza el carrito e intenta de nuevo.',
                    ]);
                }

                $details[] = [
                    'farm_product_availability_id' => $availability->id,
                    'box_type_id' => (int) $pack['box_type_id'],
                    'bunches' => $priced['bunches'],
                    'stems_per_bunch' => $priced['stems_per_bunch'],
                    'boxes' => $packEst['boxes'],
                    'stems_per_box' => $packEst['stems_per_box'],
                    'total_stems' => $priced['total_stems'],
                    'price_per_stem' => $priced['price_per_stem'],
                    'unit_price' => $priced['price_per_stem'],
                    'subtotal' => $priced['subtotal'],
                ];

                $total += $priced['subtotal'];
            }

            foreach ($stemsByAvailability as $availabilityId => $requested) {
                $availability = FarmProductAvailability::query()
                    ->whereKey($availabilityId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($requested > $availability->remainingStems()) {
                    throw ValidationException::withMessages([
                        'cart' => 'No hay tallos suficientes para completar la compra.',
                    ]);
                }
            }

            $order = Order::query()->create([
                'buyer_id' => $buyer->id,
                'status' => 'pending',
                'total' => round($total, 2),
                'notes' => 'Pedido creado desde portal de comprador',
                'payment_condition' => $payload['payment_condition'],
                'credit_days' => $payload['payment_condition'] === Order::PAYMENT_CREDIT
                    ? ($payload['credit_days'] ?? null)
                    : null,
                'cargo_agency_id' => (int) $payload['cargo_agency_id'],
                'shipping_method' => $payload['shipping_method'],
                'destination_country_id' => (int) $payload['destination_country_id'],
                'destination_city' => $payload['destination_city'] ?? null,
                'destination_airport' => $payload['shipping_method'] === Order::SHIPPING_AIR
                    ? ($payload['destination_airport'] ?? null)
                    : null,
                'destination_port' => $payload['shipping_method'] === Order::SHIPPING_SEA
                    ? ($payload['destination_port'] ?? null)
                    : null,
                'marking' => $payload['marking'] ?? null,
            ]);

            foreach ($details as $detail) {
                $order->details()->create($detail);
            }

            $this->fulfillmentService->syncForOrder($order->fresh('details'));

            $cart->items()->delete();
            $cart->update(['status' => BuyerCart::STATUS_CONVERTED]);

            $this->activityLogger->log(
                'order_created',
                "Pedido #{$order->id} creado desde portal comprador",
                Order::class,
                $order->id,
            );

            return $order->fresh(['details', 'farmFulfillments', 'cargoAgency', 'destinationCountry']);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function assertLogistics(array $payload): void
    {
        $agency = CargoAgency::query()
            ->whereKey($payload['cargo_agency_id'])
            ->where('active', true)
            ->first();

        if (! $agency) {
            throw ValidationException::withMessages([
                'cargo_agency_id' => 'Debes seleccionar una agencia de carga activa.',
            ]);
        }

        if (! in_array($payload['shipping_method'], [Order::SHIPPING_AIR, Order::SHIPPING_SEA], true)) {
            throw ValidationException::withMessages([
                'shipping_method' => 'El medio de transporte no es válido.',
            ]);
        }

        if (! Country::query()->whereKey($payload['destination_country_id'])->exists()) {
            throw ValidationException::withMessages([
                'destination_country_id' => 'Debes seleccionar un país de destino.',
            ]);
        }

        if ($payload['shipping_method'] === Order::SHIPPING_AIR
            && blank($payload['destination_airport'] ?? null)) {
            throw ValidationException::withMessages([
                'destination_airport' => 'Indica el aeropuerto de destino para vía aérea.',
            ]);
        }

        if ($payload['shipping_method'] === Order::SHIPPING_SEA
            && blank($payload['destination_port'] ?? null)) {
            throw ValidationException::withMessages([
                'destination_port' => 'Indica el puerto de destino para vía marítima.',
            ]);
        }
    }

    private function assertPayment(Buyer $buyer, string $paymentCondition, ?int $creditDays): void
    {
        if ($paymentCondition === Order::PAYMENT_CASH) {
            return;
        }

        if ($paymentCondition !== Order::PAYMENT_CREDIT) {
            throw ValidationException::withMessages([
                'payment_condition' => 'Forma de pago no válida.',
            ]);
        }

        if (! $buyer->credit_allowed) {
            throw ValidationException::withMessages([
                'payment_condition' => 'Este comprador no tiene crédito autorizado.',
            ]);
        }

        $maxDays = (int) ($buyer->credit_days_default ?? 0);

        if ($maxDays <= 0 || $creditDays === null || $creditDays < 1 || $creditDays > $maxDays) {
            throw ValidationException::withMessages([
                'credit_days' => "Los días de crédito deben estar entre 1 y {$maxDays}.",
            ]);
        }
    }
}
