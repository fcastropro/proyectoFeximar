<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderFarmFulfillment;
use Illuminate\Support\Collection;

class OrderFulfillmentService
{
    /**
     * Crea un fulfillment por cada finca involucrada en el pedido (idempotente).
     */
    public function syncForOrder(Order $order): void
    {
        $farmIds = $this->farmIdsFromOrder($order);

        foreach ($farmIds as $farmId) {
            OrderFarmFulfillment::query()->firstOrCreate(
                [
                    'order_id' => $order->id,
                    'farm_id' => $farmId,
                ],
                [
                    'status' => 'pending',
                    'received_at' => now(),
                ]
            );
        }
    }

    /**
     * @return Collection<int, int>
     */
    public function farmIdsFromOrder(Order $order): Collection
    {
        $order->loadMissing([
            'details.availability.presentation.farmProduct:id,farm_id',
        ]);

        return $order->details
            ->map(fn (OrderDetail $detail) => $detail->availability?->presentation?->farmProduct?->farm_id)
            ->filter()
            ->unique()
            ->values();
    }
}
