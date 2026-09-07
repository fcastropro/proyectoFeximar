<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\FarmProduct;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FarmProductController extends Controller
{
    public function index(): Response
    {
        $farmProducts = FarmProduct::query()
            ->with([
                'farm:id,name',
                'product:id,name,variety_id,category,variety,color',
                'product.variety:id,flower_type_id,name,color',
                'product.variety.flowerType:id,name',
            ])
            ->orderByDesc('id')
            ->get()
            ->map(fn (FarmProduct $farmProduct) => $this->transformFarmProduct($farmProduct));

        return Inertia::render('Admin/FarmProducts/Index', [
            'farmProducts' => $farmProducts,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/FarmProducts/Create', [
            'farms' => $this->activeFarms(),
            'products' => $this->productOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        FarmProduct::create($this->validatedData($request));

        return redirect()
            ->route('admin.farm-products.index')
            ->with('success', 'Producto asociado a la finca correctamente.');
    }

    public function edit(FarmProduct $farmProduct): Response
    {
        return Inertia::render('Admin/FarmProducts/Edit', [
            'farmProduct' => [
                'id' => $farmProduct->id,
                'farm_id' => $farmProduct->farm_id,
                'product_id' => $farmProduct->product_id,
                'active' => (bool) $farmProduct->active,
            ],
            'farms' => $this->activeFarms(),
            'products' => $this->productOptions(),
        ]);
    }

    public function update(Request $request, FarmProduct $farmProduct): RedirectResponse
    {
        $farmProduct->update($this->validatedData($request, $farmProduct));

        return redirect()
            ->route('admin.farm-products.index')
            ->with('success', 'Asociación actualizada correctamente.');
    }

    public function destroy(FarmProduct $farmProduct): RedirectResponse
    {
        $farmProduct->delete();

        return redirect()
            ->route('admin.farm-products.index')
            ->with('success', 'Asociación eliminada correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?FarmProduct $farmProduct = null): array
    {
        $validated = $request->validate([
            'farm_id' => ['required', 'integer', 'exists:farms,id'],
            'product_id' => [
                'required',
                'integer',
                'exists:products,id',
                Rule::unique('farm_products', 'product_id')
                    ->where(fn ($query) => $query->where('farm_id', $request->integer('farm_id')))
                    ->ignore($farmProduct?->id),
            ],
            'active' => ['boolean'],
        ]);

        $validated['active'] = $request->boolean('active');

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    private function transformFarmProduct(FarmProduct $farmProduct): array
    {
        $product = $farmProduct->product;
        $relatedVariety = $product?->relationLoaded('variety')
            ? $product->getRelation('variety')
            : null;

        return [
            'id' => $farmProduct->id,
            'farm_name' => $farmProduct->farm?->name,
            'product_name' => $product?->name,
            'flower_type' => $relatedVariety?->flowerType?->name
                ?? $product?->getAttributes()['category']
                ?? null,
            'variety_name' => $relatedVariety?->name
                ?? $product?->getAttributes()['variety']
                ?? null,
            'color' => $relatedVariety?->color
                ?? $product?->getAttributes()['color']
                ?? null,
            'active' => (bool) $farmProduct->active,
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{id:int,name:string}>
     */
    private function activeFarms()
    {
        return Farm::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{id:int,label:string}>
     */
    private function productOptions()
    {
        return Product::query()
            ->with([
                'variety:id,flower_type_id,name,color',
                'variety.flowerType:id,name',
            ])
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->map(function (Product $product) {
                $relatedVariety = $product->getRelation('variety');
                $flowerType = $relatedVariety?->flowerType?->name
                    ?? $product->getAttributes()['category']
                    ?? 'Sin tipo';
                $varietyName = $relatedVariety?->name
                    ?? $product->getAttributes()['variety']
                    ?? 'Sin variedad';
                $color = $relatedVariety?->color
                    ?? $product->getAttributes()['color']
                    ?? 'Sin color';

                return [
                    'id' => $product->id,
                    'label' => "{$flowerType} - {$varietyName} - {$color}",
                ];
            })
            ->values();
    }
}
