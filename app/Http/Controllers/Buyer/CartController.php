<?php

namespace App\Http\Controllers\Buyer;

use App\Models\BuyerCartItem;
use App\Models\CargoAgency;
use App\Models\Country;
use App\Models\PresentationBoxConfig;
use App\Services\BuyerCartService;
use App\Services\BuyerCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends BaseBuyerController
{
    public function __construct(
        private readonly BuyerCartService $cartService,
        private readonly BuyerCheckoutService $checkoutService,
    ) {}

    public function index(Request $request): Response
    {
        $buyer = $this->currentBuyer($request);
        $items = $this->cartItemsPayload($buyer, $request->user());

        return Inertia::render('Buyer/Cart/Index', [
            'items' => $items,
            'total' => round((float) collect($items)->sum('subtotal'), 2),
        ]);
    }

    public function checkoutForm(Request $request): Response
    {
        $buyer = $this->currentBuyer($request);
        $items = $this->cartItemsPayload($buyer, $request->user(), withPackagingOptions: true);

        if ($items === []) {
            return redirect()->route('buyer.cart.index')
                ->with('error', 'El carrito está vacío.');
        }

        return Inertia::render('Buyer/Checkout/Index', [
            'items' => $items,
            'total' => round((float) collect($items)->sum('subtotal'), 2),
            'paymentOptions' => [
                'credit_allowed' => (bool) $buyer->credit_allowed,
                'credit_days_default' => $buyer->credit_days_default,
            ],
            'cargoAgencies' => CargoAgency::query()
                ->where('active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'countries' => Country::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $buyer = $this->currentBuyer($request);

        $validated = $request->validate([
            'farm_product_availability_id' => ['required', 'integer', 'exists:farm_product_availabilities,id'],
            'bunches' => ['required', 'integer', 'min:1'],
        ]);

        $this->cartService->addItem(
            $buyer,
            $request->user(),
            (int) $validated['farm_product_availability_id'],
            (int) $validated['bunches'],
        );

        return redirect()
            ->route('buyer.cart.index')
            ->with('success', 'Producto agregado al carrito.');
    }

    public function update(Request $request, BuyerCartItem $item): RedirectResponse
    {
        $buyer = $this->currentBuyer($request);

        $validated = $request->validate([
            'bunches' => ['required', 'integer', 'min:1'],
        ]);

        $this->cartService->updateItem($buyer, $request->user(), $item, (int) $validated['bunches']);

        return redirect()
            ->route('buyer.cart.index')
            ->with('success', 'Cantidad actualizada.');
    }

    public function destroy(Request $request, BuyerCartItem $item): RedirectResponse
    {
        $buyer = $this->currentBuyer($request);
        $this->cartService->removeItem($buyer, $request->user(), $item);

        return redirect()
            ->route('buyer.cart.index')
            ->with('success', 'Línea eliminada del carrito.');
    }

    public function clear(Request $request): RedirectResponse
    {
        $buyer = $this->currentBuyer($request);
        $this->cartService->clear($buyer, $request->user());

        return redirect()
            ->route('buyer.cart.index')
            ->with('success', 'Carrito vaciado.');
    }

    public function checkout(Request $request): RedirectResponse
    {
        $buyer = $this->currentBuyer($request);

        $validated = $request->validate([
            'payment_condition' => ['required', 'string', 'in:cash,credit'],
            'credit_days' => ['nullable', 'integer', 'min:1'],
            'cargo_agency_id' => ['required', 'integer', 'exists:cargo_agencies,id'],
            'shipping_method' => ['required', 'string', 'in:air,sea'],
            'destination_country_id' => ['required', 'integer', 'exists:countries,id'],
            'destination_city' => ['nullable', 'string', 'max:255'],
            'destination_airport' => ['nullable', 'string', 'max:255'],
            'destination_port' => ['nullable', 'string', 'max:255'],
            'marking' => ['nullable', 'string', 'max:2000'],
            'packaging' => ['required', 'array', 'min:1'],
            'packaging.*.cart_item_id' => ['required', 'integer', 'exists:buyer_cart_items,id'],
            'packaging.*.box_type_id' => ['required', 'integer', 'exists:box_types,id'],
        ]);

        $order = $this->checkoutService->checkout($buyer, $request->user(), $validated);

        return redirect()
            ->route('buyer.orders.show', $order)
            ->with('success', 'Pedido confirmado correctamente.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function cartItemsPayload($buyer, $user, bool $withPackagingOptions = false): array
    {
        $cart = $this->cartService->activeCart($buyer, $user);
        $cart->load([
            'items.availability:id,farm_product_presentation_id,year,week_number,available_stems,reserved_stems,price_per_stem',
            'items.availability.presentation:id,farm_product_id,stem_length_cm,stems_per_bunch',
            'items.availability.presentation.farmProduct:id,farm_id,product_id',
            'items.availability.presentation.farmProduct.farm:id,name',
            'items.availability.presentation.farmProduct.product:id,name,variety_id,variety,image_path',
            'items.availability.presentation.farmProduct.product.variety:id,name',
            'items.availability.presentation.boxConfigs' => fn ($q) => $q->where('active', true)->with('boxType:id,code,name'),
        ]);

        return $cart->items->map(function (BuyerCartItem $item) use ($withPackagingOptions) {
            $product = $item->availability?->presentation?->farmProduct?->product;
            $variety = $product?->relationLoaded('variety') ? $product->getRelation('variety') : null;
            $presentation = $item->availability?->presentation;

            $row = [
                'id' => $item->id,
                'availability_id' => $item->farm_product_availability_id,
                'image_url' => $product?->imageUrl(),
                'product_name' => $product?->name,
                'variety' => $variety?->name ?? ($product?->getAttributes()['variety'] ?? null),
                'farm_name' => $item->availability?->presentation?->farmProduct?->farm?->name,
                'stem_length_cm' => $presentation?->stem_length_cm,
                'bunches' => $item->bunches,
                'stems_per_bunch' => $item->stems_per_bunch_snapshot,
                'total_stems' => $item->total_stems,
                'price_per_stem' => (float) $item->price_per_stem_snapshot,
                'subtotal' => (float) $item->subtotal,
                'effective_stems' => $item->availability?->remainingStems() ?? 0,
            ];

            if ($withPackagingOptions) {
                $row['box_options'] = ($presentation?->boxConfigs ?? collect())
                    ->filter(fn (PresentationBoxConfig $c) => $c->boxType)
                    ->map(function (PresentationBoxConfig $c) use ($item) {
                        $stemsPerBox = (int) $c->stems_per_box;
                        $boxes = $stemsPerBox > 0 ? (int) ceil($item->total_stems / $stemsPerBox) : 0;

                        return [
                            'id' => $c->box_type_id,
                            'code' => $c->boxType?->code,
                            'name' => $c->boxType?->name,
                            'stems_per_box' => $stemsPerBox,
                            'bunches_per_box' => $c->bunches_per_box !== null ? (int) $c->bunches_per_box : null,
                            'estimated_boxes' => max(1, $boxes),
                            'partial' => $stemsPerBox > 0 && ($item->total_stems % $stemsPerBox) !== 0,
                        ];
                    })
                    ->values()
                    ->all();
            }

            return $row;
        })->values()->all();
    }
}
