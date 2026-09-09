<?php

namespace App\Http\Controllers\Farm;

use App\Models\OrderDetail;
use App\Models\OrderFarmFulfillment;
use App\Services\Admin\ActivityLogger;
use App\Services\FarmAvailabilityReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends BaseFarmController
{
    public function __construct(
        private readonly FarmAvailabilityReservationService $reservationService,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function index(Request $request): Response
    {
        $farm = $this->currentFarm($request);

        $fulfillments = OrderFarmFulfillment::query()
            ->with(['order:id,buyer_id,created_at,status', 'order.buyer:id,company_name'])
            ->where('farm_id', $farm->id)
            ->orderByDesc('id')
            ->get()
            ->map(function (OrderFarmFulfillment $fulfillment) use ($farm) {
                $lines = $this->farmLines($fulfillment->order_id, $farm->id);

                return [
                    'id' => $fulfillment->id,
                    'order_id' => $fulfillment->order_id,
                    'buyer_name' => $fulfillment->order?->buyer?->company_name,
                    'order_date' => $fulfillment->order?->created_at?->format('Y-m-d H:i'),
                    'status' => $fulfillment->status,
                    'lines_count' => $lines->count(),
                    'total_stems' => (int) $lines->sum('total_stems'),
                    'subtotal' => round((float) $lines->sum('subtotal'), 2),
                ];
            });

        return Inertia::render('Farm/Orders/Index', [
            'fulfillments' => $fulfillments,
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    public function show(Request $request, OrderFarmFulfillment $fulfillment): Response
    {
        $farm = $this->currentFarm($request);
        $this->assertOwned($fulfillment, $farm->id);

        $fulfillment->load([
            'order.buyer:id,company_name,contact_name,email',
            'order.cargoAgency:id,name',
            'order.destinationCountry:id,name',
        ]);
        $lines = $this->farmLines($fulfillment->order_id, $farm->id)->map(function (OrderDetail $detail) {
            $availability = $detail->availability;
            $presentation = $availability?->presentation;
            $product = $presentation?->farmProduct?->product;
            $variety = $product?->relationLoaded('catalogVariety') ? $product->getRelation('catalogVariety') : null;

            return [
                'id' => $detail->id,
                'week_label' => $availability
                    ? "Semana {$availability->week_number}/{$availability->year}"
                    : '—',
                'product_name' => $product?->name,
                'variety_name' => $variety?->name ?? $product?->getAttributes()['variety'] ?? null,
                'stem_length_cm' => $presentation?->stem_length_cm,
                'bunches' => $detail->bunches,
                'stems_per_bunch' => $detail->stems_per_bunch,
                'box_type' => $detail->boxType?->code,
                'boxes' => $detail->boxes,
                'stems_per_box' => $detail->stems_per_box,
                'total_stems' => $detail->total_stems,
                'price_per_stem' => $detail->price_per_stem,
                'unit_price' => $detail->unit_price,
                'subtotal' => $detail->subtotal,
            ];
        });

        $order = $fulfillment->order;

        return Inertia::render('Farm/Orders/Show', [
            'fulfillment' => [
                'id' => $fulfillment->id,
                'order_id' => $fulfillment->order_id,
                'status' => $fulfillment->status,
                'buyer_name' => $order?->buyer?->company_name,
                'buyer_contact' => $order?->buyer?->contact_name,
                'order_date' => $order?->created_at?->format('Y-m-d H:i'),
                'rejection_reason' => $fulfillment->rejection_reason,
                'stems_reserved' => $fulfillment->stems_reserved,
                'shipping' => [
                    'cargo_agency' => $order?->cargoAgency?->name,
                    'shipping_method' => $order?->shipping_method,
                    'destination_country' => $order?->destinationCountry?->name,
                    'destination_city' => $order?->destination_city,
                    'destination_airport' => $order?->destination_airport,
                    'destination_port' => $order?->destination_port,
                    'marking' => $order?->marking,
                    'payment_condition' => $order?->payment_condition,
                    'credit_days' => $order?->credit_days,
                ],
                'timeline' => [
                    ['key' => 'received', 'label' => 'Pedido recibido', 'at' => $fulfillment->received_at?->format('Y-m-d H:i')],
                    ['key' => 'accepted', 'label' => 'Aceptado', 'at' => $fulfillment->accepted_at?->format('Y-m-d H:i')],
                    ['key' => 'preparing', 'label' => 'En preparación', 'at' => $fulfillment->prepared_at?->format('Y-m-d H:i')],
                    ['key' => 'ready', 'label' => 'Listo', 'at' => $fulfillment->ready_at?->format('Y-m-d H:i')],
                    ['key' => 'dispatched', 'label' => 'Despachado', 'at' => $fulfillment->dispatched_at?->format('Y-m-d H:i')],
                    ['key' => 'completed', 'label' => 'Completado', 'at' => $fulfillment->completed_at?->format('Y-m-d H:i')],
                ],
                'allowed_transitions' => OrderFarmFulfillment::TRANSITIONS[$fulfillment->status] ?? [],
                'lines' => $lines,
                'subtotal' => round((float) $lines->sum('subtotal'), 2),
            ],
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    public function transition(Request $request, OrderFarmFulfillment $fulfillment): RedirectResponse
    {
        $farm = $this->currentFarm($request);
        $this->assertOwned($fulfillment, $farm->id);

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(OrderFarmFulfillment::STATUSES)],
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $newStatus = $validated['status'];

        if (! $fulfillment->canTransitionTo($newStatus)) {
            return back()->withErrors([
                'status' => 'Transición de estado no permitida.',
            ]);
        }

        if ($newStatus === 'accepted') {
            $this->reservationService->reserveOnAccept($fulfillment);
            $this->activityLogger->log(
                'fulfillment_accepted',
                "Fulfillment #{$fulfillment->id} aceptado (pedido #{$fulfillment->order_id})",
                OrderFarmFulfillment::class,
                $fulfillment->id,
            );

            return redirect()
                ->route('farm.orders.show', $fulfillment->id)
                ->with('success', 'Pedido aceptado y tallos reservados.');
        }

        if (in_array($newStatus, ['rejected', 'cancelled'], true) && $fulfillment->stems_reserved) {
            $this->reservationService->releaseReservation($fulfillment);
            $fulfillment->refresh();
        }

        $payload = ['status' => $newStatus];

        if ($newStatus === 'rejected') {
            $payload['rejected_at'] = now();
            $payload['rejection_reason'] = $validated['rejection_reason'] ?? null;

            if (empty($payload['rejection_reason'])) {
                return back()->withErrors([
                    'rejection_reason' => 'Debes indicar el motivo del rechazo.',
                ]);
            }
        }

        if ($newStatus === 'preparing') {
            $payload['prepared_at'] = now();
        }
        if ($newStatus === 'ready') {
            $payload['ready_at'] = now();
        }
        if ($newStatus === 'dispatched') {
            $payload['dispatched_at'] = now();
        }
        if ($newStatus === 'completed') {
            $payload['completed_at'] = now();
        }
        if ($newStatus === 'cancelled') {
            // no extra timestamp field beyond status
        }

        $fulfillment->update($payload);

        $this->activityLogger->log(
            'fulfillment_status_changed',
            "Fulfillment #{$fulfillment->id} cambió a {$newStatus} (pedido #{$fulfillment->order_id})",
            OrderFarmFulfillment::class,
            $fulfillment->id,
        );

        return redirect()
            ->route('farm.orders.show', $fulfillment->id)
            ->with('success', 'Estado actualizado correctamente.');
    }

    private function assertOwned(OrderFarmFulfillment $fulfillment, int $farmId): void
    {
        abort_unless($fulfillment->farm_id === $farmId, 403, 'No puedes ver pedidos de otra finca.');
    }

    /**
     * @return \Illuminate\Support\Collection<int, OrderDetail>
     */
    private function farmLines(int $orderId, int $farmId)
    {
        return OrderDetail::query()
            ->with([
                'boxType:id,code,name',
                'availability:id,farm_product_presentation_id,year,week_number',
                'availability.presentation:id,farm_product_id,stem_length_cm',
                'availability.presentation.farmProduct:id,farm_id,product_id',
                'availability.presentation.farmProduct.product:id,name,variety_id,variety',
                'availability.presentation.farmProduct.product.catalogVariety:id,name',
            ])
            ->where('order_id', $orderId)
            ->whereHas('availability.presentation.farmProduct', fn ($q) => $q->where('farm_id', $farmId))
            ->get();
    }

    /**
     * @return array<string, string>
     */
    private function statusLabels(): array
    {
        return [
            'pending' => 'Pendiente',
            'accepted' => 'Aceptado',
            'rejected' => 'Rechazado',
            'preparing' => 'En preparación',
            'ready' => 'Listo',
            'dispatched' => 'Despachado',
            'completed' => 'Completado',
            'cancelled' => 'Cancelado',
        ];
    }
}
