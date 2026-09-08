<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\FarmProductAvailability;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\PresentationBoxConfig;
use App\Services\Admin\ActivityLogger;
use App\Services\OrderFulfillmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderFulfillmentService $fulfillmentService,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function index(Request $request): Response
    {
        $query = Order::query()
            ->with('buyer:id,company_name')
            ->orderByDesc('id');

        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(function ($builder) use ($q) {
                $builder->where('id', $q)
                    ->orWhereHas('buyer', fn ($b) => $b->where('company_name', 'like', "%{$q}%"));
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $orders = $query
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Order $order) => [
                'id' => $order->id,
                'buyer_name' => $order->buyer?->company_name,
                'status' => $order->status,
                'total' => $order->total,
                'created_at' => $order->created_at?->format('Y-m-d H:i'),
            ]);

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'filters' => [
                'q' => $request->input('q'),
                'status' => $request->input('status'),
            ],
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Orders/Create', [
            'buyers' => $this->activeBuyers(),
            'availabilities' => $this->activeAvailabilityOptions(),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $this->validatedPayload($request);

        $order = null;
        DB::transaction(function () use ($payload, &$order) {
            $order = Order::create([
                'buyer_id' => $payload['buyer_id'],
                'status' => $payload['status'],
                'notes' => $payload['notes'],
                'total' => $payload['total'],
            ]);

            foreach ($payload['details'] as $detail) {
                $order->details()->create($detail);
            }

            $this->fulfillmentService->syncForOrder($order->fresh('details'));
        });

        if ($order) {
            $this->activityLogger->log(
                'order_created',
                "Pedido #{$order->id} creado desde admin",
                Order::class,
                $order->id,
            );
        }

        return redirect()
            ->route('admin.orders.index')
            ->with('success', 'Pedido creado correctamente.');
    }

    public function show(Order $order): Response
    {
        $order->load([
            'buyer:id,company_name,contact_name,email,credit_allowed,credit_days_default',
            'cargoAgency:id,name',
            'destinationCountry:id,name',
            'details.boxType:id,code,name',
            'details.availability:id,farm_product_presentation_id,year,week_number,available_stems',
            'details.availability.presentation:id,farm_product_id,stem_length_cm,stems_per_bunch',
            'details.availability.presentation.farmProduct:id,farm_id,product_id',
            'details.availability.presentation.farmProduct.farm:id,name',
            'details.availability.presentation.farmProduct.product:id,name,variety_id,category,variety,color',
            'details.availability.presentation.farmProduct.product.variety:id,flower_type_id,name,color',
            'details.availability.presentation.farmProduct.product.variety.flowerType:id,name',
            'farmFulfillments.farm:id,name',
            'farmFinances.payments',
            'farmFinances.farm:id,name',
        ]);

        $financePaid = $order->farmFinances->sum(fn ($f) => $f->paidAmount());

        return Inertia::render('Admin/Orders/Show', [
            'order' => [
                'id' => $order->id,
                'buyer_name' => $order->buyer?->company_name,
                'buyer_contact' => $order->buyer?->contact_name,
                'buyer_email' => $order->buyer?->email,
                'status' => $order->status,
                'notes' => $order->notes,
                'total' => $order->total,
                'created_at' => $order->created_at?->format('Y-m-d H:i'),
                'payment_condition' => $order->payment_condition,
                'credit_days' => $order->credit_days,
                'shipping' => [
                    'cargo_agency' => $order->cargoAgency?->name,
                    'shipping_method' => $order->shipping_method,
                    'destination_country' => $order->destinationCountry?->name,
                    'destination_city' => $order->destination_city,
                    'destination_airport' => $order->destination_airport,
                    'destination_port' => $order->destination_port,
                    'marking' => $order->marking,
                ],
                'finance' => [
                    'farm_total' => round((float) $order->farmFinances->sum('amount'), 2),
                    'farm_paid' => round((float) $financePaid, 2),
                    'farm_balance' => round(max(0, (float) $order->farmFinances->sum('amount') - (float) $financePaid), 2),
                    'lines' => $order->farmFinances->map(fn ($f) => [
                        'farm_name' => $f->farm?->name,
                        'amount' => $f->amount,
                        'payment_condition' => $f->payment_condition,
                        'due_date' => $f->due_date?->format('Y-m-d'),
                        'status' => $f->status,
                        'paid' => $f->paidAmount(),
                        'balance' => $f->balance(),
                        'payments' => $f->payments->map(fn ($p) => [
                            'amount' => $p->amount,
                            'payment_date' => $p->payment_date?->format('Y-m-d') ?? $p->payment_date,
                            'reference' => $p->reference,
                        ]),
                    ]),
                ],
                'fulfillments' => $order->farmFulfillments->map(fn ($f) => [
                    'id' => $f->id,
                    'farm_name' => $f->farm?->name,
                    'status' => $f->status,
                    'received_at' => $f->received_at?->format('Y-m-d H:i'),
                    'accepted_at' => $f->accepted_at?->format('Y-m-d H:i'),
                    'rejected_at' => $f->rejected_at?->format('Y-m-d H:i'),
                    'rejection_reason' => $f->rejection_reason,
                    'prepared_at' => $f->prepared_at?->format('Y-m-d H:i'),
                    'ready_at' => $f->ready_at?->format('Y-m-d H:i'),
                    'dispatched_at' => $f->dispatched_at?->format('Y-m-d H:i'),
                    'completed_at' => $f->completed_at?->format('Y-m-d H:i'),
                ]),
                'details' => $order->details->map(function (OrderDetail $detail) {
                    $meta = $this->availabilityMeta($detail->availability);

                    return [
                        'id' => $detail->id,
                        'week_label' => $meta['week_label'],
                        'farm_name' => $meta['farm_name'],
                        'product_name' => $meta['product_name'],
                        'variety_name' => $meta['variety_name'],
                        'stem_length_cm' => $meta['stem_length_cm'],
                        'bunches' => $detail->bunches,
                        'stems_per_bunch' => $detail->stems_per_bunch,
                        'box_type' => $detail->boxType?->code ?? $detail->boxType?->name,
                        'quantity' => $detail->boxes,
                        'stems_per_box' => $detail->stems_per_box,
                        'total_stems' => $detail->total_stems,
                        'price_per_stem' => $detail->price_per_stem,
                        'unit_price' => $detail->unit_price,
                        'subtotal' => $detail->subtotal,
                    ];
                }),
            ],
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    public function edit(Order $order): Response
    {
        $order->load('details');

        return Inertia::render('Admin/Orders/Edit', [
            'order' => [
                'id' => $order->id,
                'buyer_id' => $order->buyer_id,
                'status' => $order->status,
                'notes' => $order->notes,
                'details' => $order->details->map(fn (OrderDetail $detail) => [
                    'farm_product_availability_id' => $detail->farm_product_availability_id,
                    'box_type_id' => $detail->box_type_id,
                    'quantity' => $detail->boxes,
                    'unit_price' => (float) $detail->unit_price,
                ]),
            ],
            'buyers' => $this->activeBuyers(),
            'availabilities' => $this->activeAvailabilityOptions(
                $order->details->pluck('farm_product_availability_id')->all()
            ),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $payload = $this->validatedPayload($request, $order);

        DB::transaction(function () use ($order, $payload) {
            $order->update([
                'buyer_id' => $payload['buyer_id'],
                'status' => $payload['status'],
                'notes' => $payload['notes'],
                'total' => $payload['total'],
            ]);

            $order->details()->delete();

            foreach ($payload['details'] as $detail) {
                $order->details()->create($detail);
            }

            $this->fulfillmentService->syncForOrder($order->fresh('details'));
        });

        return redirect()
            ->route('admin.orders.index')
            ->with('success', 'Pedido actualizado correctamente.');
    }

    public function destroy(Order $order): RedirectResponse
    {
        DB::transaction(function () use ($order) {
            $order->details()->delete();
            $order->delete();
        });

        return redirect()
            ->route('admin.orders.index')
            ->with('success', 'Pedido eliminado correctamente.');
    }

    /**
     * @return array{buyer_id:int,status:string,notes:?string,total:string,details:array<int,array<string,mixed>>}
     */
    private function validatedPayload(Request $request, ?Order $order = null): array
    {
        $allowedAvailabilityIds = $order
            ? $order->details()->pluck('farm_product_availability_id')->all()
            : [];

        $validator = Validator::make($request->all(), [
            'buyer_id' => ['required', 'integer', 'exists:buyers,id'],
            'status' => ['required', 'string', Rule::in(Order::STATUSES)],
            'notes' => ['nullable', 'string'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.farm_product_availability_id' => [
                'required',
                'integer',
                Rule::exists('farm_product_availabilities', 'id')->where(function ($query) use ($allowedAvailabilityIds) {
                    $query->where(function ($inner) use ($allowedAvailabilityIds) {
                        $inner->where('active', true);

                        if ($allowedAvailabilityIds !== []) {
                            $inner->orWhereIn('id', $allowedAvailabilityIds);
                        }
                    });
                }),
            ],
            'details.*.box_type_id' => [
                'required',
                'integer',
                Rule::exists('box_types', 'id')->where('active', true),
            ],
            // El formulario envía quantity (= cantidad de cajas).
            'details.*.quantity' => ['required', 'integer', 'min:1'],
            'details.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $lineSnapshots = [];

        $validator->after(function ($validator) use ($request, &$lineSnapshots) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $details = $request->input('details', []);
            $availabilityIds = collect($details)
                ->pluck('farm_product_availability_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            $availabilities = FarmProductAvailability::query()
                ->with([
                    'presentation:id,farm_product_id,stem_length_cm',
                    'presentation.farmProduct:id,product_id',
                    'presentation.farmProduct.product:id,name,variety_id,variety',
                    'presentation.farmProduct.product.variety:id,name',
                ])
                ->whereIn('id', $availabilityIds)
                ->get(['id', 'farm_product_presentation_id', 'available_stems', 'reserved_stems', 'year', 'week_number'])
                ->keyBy('id');

            $presentationIds = $availabilities
                ->pluck('farm_product_presentation_id')
                ->unique()
                ->values()
                ->all();

            $boxConfigs = PresentationBoxConfig::query()
                ->whereIn('farm_product_presentation_id', $presentationIds)
                ->where('active', true)
                ->whereHas('boxType', fn ($query) => $query->where('active', true))
                ->get(['id', 'farm_product_presentation_id', 'box_type_id', 'stems_per_box'])
                ->groupBy(fn (PresentationBoxConfig $config) => $config->farm_product_presentation_id.'-'.$config->box_type_id);

            $stemsByAvailability = [];
            $firstIndexByAvailability = [];

            foreach ($details as $index => $detail) {
                $availabilityId = (int) $detail['farm_product_availability_id'];
                $boxTypeId = (int) $detail['box_type_id'];
                $boxes = (int) $detail['quantity'];

                $availability = $availabilities->get($availabilityId);

                if (! $availability) {
                    $validator->errors()->add(
                        "details.{$index}.farm_product_availability_id",
                        'La disponibilidad seleccionada no es válida.'
                    );

                    continue;
                }

                $configKey = $availability->farm_product_presentation_id.'-'.$boxTypeId;
                $config = $boxConfigs->get($configKey)?->first();

                if (! $config) {
                    $validator->errors()->add(
                        "details.{$index}.box_type_id",
                        'El tipo de caja no está configurado para la presentación de esta disponibilidad.'
                    );

                    continue;
                }

                $stemsPerBox = (int) $config->stems_per_box;
                $totalStems = $boxes * $stemsPerBox;

                // Snapshot recalculado en backend (no confiar en Vue).
                $lineSnapshots[$index] = [
                    'stems_per_box' => $stemsPerBox,
                    'total_stems' => $totalStems,
                ];

                $stemsByAvailability[$availabilityId] = ($stemsByAvailability[$availabilityId] ?? 0) + $totalStems;
                $firstIndexByAvailability[$availabilityId] ??= $index;
            }

            foreach ($stemsByAvailability as $availabilityId => $requestedStems) {
                $availability = $availabilities->get($availabilityId);

                // Disponibilidad restante (available - reserved) para validar pedidos.
                if ($availability && $requestedStems > $availability->remainingStems()) {
                    $index = $firstIndexByAvailability[$availabilityId];
                    $label = $this->availabilityProductLabel($availability);

                    $validator->errors()->add(
                        "details.{$index}.quantity",
                        "La cantidad solicitada supera la disponibilidad semanal de {$label}."
                    );
                }
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();
        $details = [];
        $total = 0;

        foreach ($validated['details'] as $index => $detail) {
            $boxes = (int) $detail['quantity'];
            $unitPrice = round((float) $detail['unit_price'], 4);
            $subtotal = round($boxes * $unitPrice, 2);
            $total += $subtotal;

            $snapshot = $lineSnapshots[$index] ?? [
                'stems_per_box' => 0,
                'total_stems' => 0,
            ];

            $details[] = [
                'farm_product_availability_id' => (int) $detail['farm_product_availability_id'],
                'box_type_id' => (int) $detail['box_type_id'],
                'boxes' => $boxes,
                'stems_per_box' => $snapshot['stems_per_box'],
                'total_stems' => $snapshot['total_stems'],
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
            ];
        }

        return [
            'buyer_id' => (int) $validated['buyer_id'],
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
            'total' => number_format($total, 2, '.', ''),
            'details' => $details,
        ];
    }

    private function availabilityProductLabel(?FarmProductAvailability $availability): string
    {
        if (! $availability) {
            return 'esta disponibilidad';
        }

        $presentation = $availability->presentation;
        $product = $presentation?->farmProduct?->product;
        $varietyName = $product?->relationLoaded('variety')
            ? ($product->getRelation('variety')?->name)
            : null;
        $varietyName = $varietyName
            ?? $product?->getAttributes()['variety']
            ?? $product?->name
            ?? 'Producto';

        $length = $presentation?->stem_length_cm;

        return $length
            ? "{$varietyName} {$length} cm"
            : $varietyName;
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{id:int,company_name:string}>
     */
    private function activeBuyers()
    {
        return Buyer::query()
            ->where('active', true)
            ->orderBy('company_name')
            ->get(['id', 'company_name']);
    }

    /**
     * @param  array<int, int|string>  $includeIds
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function activeAvailabilityOptions(array $includeIds = [])
    {
        $includeIds = array_values(array_filter(array_map('intval', $includeIds)));

        return FarmProductAvailability::query()
            ->with([
                'presentation:id,farm_product_id,stem_length_cm',
                'presentation.farmProduct:id,farm_id,product_id',
                'presentation.farmProduct.farm:id,name',
                'presentation.farmProduct.product:id,name,variety_id,category,variety,color',
                'presentation.farmProduct.product.variety:id,flower_type_id,name,color',
                'presentation.farmProduct.product.variety.flowerType:id,name',
                'presentation.boxConfigs' => fn ($query) => $query
                    ->where('active', true)
                    ->with('boxType:id,code,name,active'),
            ])
            ->where(function ($query) use ($includeIds) {
                $query->where('active', true);

                if ($includeIds !== []) {
                    $query->orWhereIn('id', $includeIds);
                }
            })
            ->orderByDesc('year')
            ->orderByDesc('week_number')
            ->orderByDesc('id')
            ->get()
            ->map(function (FarmProductAvailability $availability) {
                $meta = $this->availabilityMeta($availability);
                $referencePrice = $availability->price_per_bunch ?? $availability->price_per_stem;

                $boxConfigs = collect($availability->presentation?->boxConfigs ?? [])
                    ->filter(fn (PresentationBoxConfig $config) => $config->boxType && $config->boxType->active)
                    ->map(fn (PresentationBoxConfig $config) => [
                        'box_type_id' => $config->box_type_id,
                        'code' => $config->boxType->code,
                        'name' => $config->boxType->name,
                        'stems_per_box' => (int) $config->stems_per_box,
                        'bunches_per_box' => $config->bunches_per_box !== null
                            ? (int) $config->bunches_per_box
                            : null,
                    ])
                    ->values()
                    ->all();

                return [
                    'id' => $availability->id,
                    'farm_product_presentation_id' => $availability->farm_product_presentation_id,
                    'label' => sprintf(
                        'Semana %d/%d - %s - %s / %s / %s - %s cm - %s tallos disponibles',
                        $meta['week_number'],
                        $meta['year'],
                        $meta['farm_name'],
                        $meta['flower_type'],
                        $meta['variety_name'],
                        $meta['color'],
                        $meta['stem_length_cm'],
                        number_format((int) $meta['available_stems'], 0, '.', ',')
                    ),
                    'year' => $meta['year'],
                    'week_number' => $meta['week_number'],
                    'available_stems' => $meta['available_stems'],
                    'price_per_stem' => $availability->price_per_stem,
                    'price_per_bunch' => $availability->price_per_bunch,
                    'reference_price' => $referencePrice !== null ? (float) $referencePrice : null,
                    'box_configs' => $boxConfigs,
                    'active' => (bool) $availability->active,
                ];
            })
            ->values();
    }

    /**
     * @return array{year:int|null,week_number:int|null,week_label:string,farm_name:string,product_name:string,flower_type:string,variety_name:string,color:string,stem_length_cm:int|string,available_stems:int}
     */
    private function availabilityMeta(?FarmProductAvailability $availability): array
    {
        $presentation = $availability?->presentation;
        $farmProduct = $presentation?->farmProduct;
        $product = $farmProduct?->product;
        $relatedVariety = $product?->relationLoaded('variety')
            ? $product->getRelation('variety')
            : null;

        $year = $availability?->year;
        $week = $availability?->week_number;

        return [
            'year' => $year,
            'week_number' => $week,
            'week_label' => ($week && $year) ? "Semana {$week}/{$year}" : '—',
            'farm_name' => $farmProduct?->farm?->name ?? 'Sin finca',
            'product_name' => $product?->name ?? 'Sin producto',
            'flower_type' => $relatedVariety?->flowerType?->name
                ?? $product?->getAttributes()['category']
                ?? 'Sin tipo',
            'variety_name' => $relatedVariety?->name
                ?? $product?->getAttributes()['variety']
                ?? 'Sin variedad',
            'color' => $relatedVariety?->color
                ?? $product?->getAttributes()['color']
                ?? 'Sin color',
            'stem_length_cm' => $presentation?->stem_length_cm ?? '—',
            'available_stems' => (int) ($availability?->available_stems ?? 0),
        ];
    }

    /**
     * @return array<int, array{value:string,label:string}>
     */
    private function statusOptions(): array
    {
        $labels = $this->statusLabels();

        return collect(Order::STATUSES)
            ->map(fn (string $status) => [
                'value' => $status,
                'label' => $labels[$status] ?? $status,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function statusLabels(): array
    {
        return [
            'pending' => 'Pendiente',
            'confirmed' => 'Confirmado',
            'processing' => 'En proceso',
            'shipped' => 'Enviado',
            'cancelled' => 'Cancelado',
        ];
    }
}
