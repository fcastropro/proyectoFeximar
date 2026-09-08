<?php

namespace App\Services;

use App\Models\FarmProductAvailability;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderFarmFulfillment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FarmAvailabilityReservationService
{
    /**
     * Reserva tallos al aceptar el fulfillment de una finca.
     */
    public function reserveOnAccept(OrderFarmFulfillment $fulfillment): void
    {
        if ($fulfillment->stems_reserved) {
            throw ValidationException::withMessages([
                'status' => 'Los tallos de este fulfillment ya fueron reservados.',
            ]);
        }

        DB::transaction(function () use ($fulfillment) {
            $fulfillment = OrderFarmFulfillment::query()
                ->lockForUpdate()
                ->findOrFail($fulfillment->id);

            if ($fulfillment->stems_reserved) {
                throw ValidationException::withMessages([
                    'status' => 'Los tallos de este fulfillment ya fueron reservados.',
                ]);
            }

            $lines = $this->farmLines($fulfillment->order_id, $fulfillment->farm_id);
            $stemsByAvailability = $lines
                ->groupBy('farm_product_availability_id')
                ->map(fn ($group) => (int) $group->sum('total_stems'));

            $availabilityIds = $stemsByAvailability->keys()->all();

            $availabilities = FarmProductAvailability::query()
                ->whereIn('id', $availabilityIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($stemsByAvailability as $availabilityId => $stems) {
                $availability = $availabilities->get($availabilityId);

                if (! $availability) {
                    throw ValidationException::withMessages([
                        'status' => 'No se encontró una disponibilidad asociada al pedido.',
                    ]);
                }

                if ($availability->remainingStems() < $stems) {
                    throw ValidationException::withMessages([
                        'status' => 'No hay saldo suficiente de tallos para aceptar este pedido.',
                    ]);
                }

                $availability->increment('reserved_stems', $stems);
            }

            $fulfillment->update([
                'status' => 'accepted',
                'accepted_at' => now(),
                'stems_reserved' => true,
            ]);
        });
    }

    /**
     * Libera tallos reservados (cancelación / rechazo posterior a reserva).
     */
    public function releaseReservation(OrderFarmFulfillment $fulfillment): void
    {
        if (! $fulfillment->stems_reserved) {
            return;
        }

        DB::transaction(function () use ($fulfillment) {
            $fulfillment = OrderFarmFulfillment::query()
                ->lockForUpdate()
                ->findOrFail($fulfillment->id);

            if (! $fulfillment->stems_reserved) {
                return;
            }

            $lines = $this->farmLines($fulfillment->order_id, $fulfillment->farm_id);
            $stemsByAvailability = $lines
                ->groupBy('farm_product_availability_id')
                ->map(fn ($group) => (int) $group->sum('total_stems'));

            $availabilities = FarmProductAvailability::query()
                ->whereIn('id', $stemsByAvailability->keys()->all())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($stemsByAvailability as $availabilityId => $stems) {
                $availability = $availabilities->get($availabilityId);

                if (! $availability) {
                    continue;
                }

                $newReserved = max(0, (int) $availability->reserved_stems - $stems);
                $availability->update(['reserved_stems' => $newReserved]);
            }

            $fulfillment->update(['stems_reserved' => false]);
        });
    }

    /**
     * @return \Illuminate\Support\Collection<int, OrderDetail>
     */
    private function farmLines(int $orderId, int $farmId)
    {
        return OrderDetail::query()
            ->where('order_id', $orderId)
            ->whereHas('availability.presentation.farmProduct', fn ($q) => $q->where('farm_id', $farmId))
            ->get();
    }
}
