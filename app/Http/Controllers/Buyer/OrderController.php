<?php

namespace App\Http\Controllers\Buyer;

use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends BaseBuyerController
{
    public function index(Request $request): Response
    {
        $buyer = $this->currentBuyer($request);

        $orders = Order::query()
            ->where('buyer_id', $buyer->id)
            ->with(['farmFulfillments.farm:id,name', 'cargoAgency:id,name'])
            ->orderByDesc('id')
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'created_at' => $order->created_at?->format('Y-m-d H:i'),
                'total' => (float) $order->total,
                'status' => $order->status,
                'payment_condition' => $order->payment_condition,
                'credit_days' => $order->credit_days,
                'shipping_method' => $order->shipping_method,
                'cargo_agency' => $order->cargoAgency?->name,
                'farms' => $order->farmFulfillments->map(fn ($f) => [
                    'farm_name' => $f->farm?->name,
                    'status' => $f->status,
                ])->all(),
            ]);

        return Inertia::render('Buyer/Orders/Index', [
            'orders' => $orders,
        ]);
    }

    public function show(Request $request, Order $order): Response
    {
        $buyer = $this->currentBuyer($request);

        if ($order->buyer_id !== $buyer->id) {
            abort(403);
        }

        $order->load([
            'details.boxType:id,code,name',
            'details.availability.presentation.farmProduct.farm:id,name',
            'details.availability.presentation.farmProduct.product:id,name,variety_id,variety,image_path',
            'details.availability.presentation.farmProduct.product.catalogVariety:id,name',
            'farmFulfillments.farm:id,name',
            'cargoAgency:id,name',
            'destinationCountry:id,name',
        ]);

        return Inertia::render('Buyer/Orders/Show', [
            'order' => [
                'id' => $order->id,
                'created_at' => $order->created_at?->format('Y-m-d H:i'),
                'total' => (float) $order->total,
                'status' => $order->status,
                'payment_condition' => $order->payment_condition,
                'credit_days' => $order->credit_days,
                'notes' => $order->notes,
                'shipping' => [
                    'cargo_agency' => $order->cargoAgency?->name,
                    'shipping_method' => $order->shipping_method,
                    'destination_country' => $order->destinationCountry?->name,
                    'destination_city' => $order->destination_city,
                    'destination_airport' => $order->destination_airport,
                    'destination_port' => $order->destination_port,
                    'marking' => $order->marking,
                ],
                'fulfillments' => $order->farmFulfillments->map(fn ($f) => [
                    'farm_name' => $f->farm?->name,
                    'status' => $f->status,
                    'accepted_at' => $f->accepted_at?->format('Y-m-d H:i'),
                    'rejected_at' => $f->rejected_at?->format('Y-m-d H:i'),
                    'ready_at' => $f->ready_at?->format('Y-m-d H:i'),
                ]),
                'details' => $order->details->map(function (OrderDetail $detail) {
                    $product = $detail->availability?->presentation?->farmProduct?->product;
                    $variety = $product?->relationLoaded('catalogVariety') ? $product->getRelation('catalogVariety') : null;

                    return [
                        'product_name' => $product?->name,
                        'variety' => $variety?->name ?? ($product?->getAttributes()['variety'] ?? null),
                        'farm_name' => $detail->availability?->presentation?->farmProduct?->farm?->name,
                        'stem_length_cm' => $detail->availability?->presentation?->stem_length_cm,
                        'bunches' => $detail->bunches,
                        'stems_per_bunch' => $detail->stems_per_bunch,
                        'box_type' => $detail->boxType?->code ?? $detail->boxType?->name,
                        'boxes' => $detail->boxes,
                        'stems_per_box' => $detail->stems_per_box,
                        'total_stems' => $detail->total_stems,
                        'price_per_stem' => $detail->price_per_stem !== null
                            ? (float) $detail->price_per_stem
                            : (float) $detail->unit_price,
                        'subtotal' => (float) $detail->subtotal,
                        'image_url' => $product?->imageUrl(),
                    ];
                }),
            ],
        ]);
    }
}
