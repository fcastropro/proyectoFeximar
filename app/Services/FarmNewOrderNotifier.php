<?php

namespace App\Services;

use App\Models\Farm;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderFarmFulfillment;
use App\Notifications\NewOrderForFarmNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class FarmNewOrderNotifier
{
    public function notifyFarmsForOrder(Order $order): void
    {
        $order->loadMissing([
            'buyer',
            'farmFulfillments.farm.farmUsers.user',
            'details.availability.presentation.farmProduct.farm',
            'details.availability.presentation.farmProduct.product.catalogVariety',
            'details.availability.presentation',
        ]);

        $buyer = $order->buyer;

        if (! $buyer) {
            Log::warning('FarmNewOrderNotifier: pedido sin comprador.', [
                'order_id' => $order->id,
            ]);

            return;
        }

        $notifiedEmails = [];

        foreach ($order->farmFulfillments as $fulfillment) {
            $farm = $fulfillment->farm;

            if (! $farm) {
                continue;
            }

            $email = $this->resolveFarmEmail($farm);

            if (! $email) {
                Log::warning('FarmNewOrderNotifier: finca sin email válido.', [
                    'order_id' => $order->id,
                    'farm_id' => $farm->id,
                    'fulfillment_id' => $fulfillment->id,
                ]);

                continue;
            }

            // Evitar duplicados al mismo destinatario (misma finca/email).
            $dedupeKey = strtolower($email).'|'.$farm->id;

            if (isset($notifiedEmails[$dedupeKey])) {
                continue;
            }

            $lines = $this->farmLines($order, $farm->id);
            $farmTotal = round((float) $lines->sum('subtotal_raw'), 2);

            try {
                Notification::route('mail', $email)
                    ->notify(new NewOrderForFarmNotification(
                        $order,
                        $fulfillment,
                        $farm,
                        $buyer,
                        $lines->map(fn (array $line) => [
                            'product' => $line['product'],
                            'variety' => $line['variety'],
                            'color' => $line['color'],
                            'length' => $line['length'],
                            'bunches' => $line['bunches'],
                            'total_stems' => $line['total_stems'],
                            'subtotal' => $line['subtotal'],
                        ])->values(),
                        $farmTotal,
                    ));

                $notifiedEmails[$dedupeKey] = true;
            } catch (Throwable $exception) {
                Log::error('Error al enviar NewOrderForFarmNotification.', [
                    'order_id' => $order->id,
                    'farm_id' => $farm->id,
                    'email' => $email,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }
    }

    public function resolveFarmEmail(Farm $farm): ?string
    {
        if (filled($farm->email)) {
            return trim((string) $farm->email);
        }

        $farm->loadMissing(['farmUsers.user']);

        $primary = $farm->farmUsers
            ->where('active', true)
            ->sortBy(fn ($farmUser) => ($farmUser->role === 'manager' ? 0 : 1).'-'.$farmUser->id)
            ->first();

        $email = $primary?->user?->email;

        return filled($email) ? trim((string) $email) : null;
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function farmLines(Order $order, int $farmId)
    {
        return $order->details
            ->filter(function (OrderDetail $detail) use ($farmId) {
                return (int) ($detail->availability?->presentation?->farmProduct?->farm_id) === $farmId;
            })
            ->map(function (OrderDetail $detail) {
                $presentation = $detail->availability?->presentation;
                $product = $presentation?->farmProduct?->product;
                $relatedVariety = $product?->relationLoaded('catalogVariety')
                    ? $product->getRelation('catalogVariety')
                    : null;

                $subtotal = round((float) $detail->subtotal, 2);

                return [
                    'product' => $product?->name ?? '—',
                    'variety' => $relatedVariety?->name
                        ?? ($product?->getAttributes()['variety'] ?? '—'),
                    'color' => $product?->getAttributes()['color'] ?? '—',
                    'length' => $presentation?->stem_length_cm
                        ? $presentation->stem_length_cm.' cm'
                        : '—',
                    'bunches' => $detail->bunches ?? '—',
                    'total_stems' => $detail->total_stems ?? '—',
                    'subtotal' => number_format($subtotal, 2, '.', ','),
                    'subtotal_raw' => $subtotal,
                ];
            })
            ->values();
    }
}
