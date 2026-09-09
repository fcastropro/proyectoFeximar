<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FarmProduct;
use App\Models\FarmProductPresentation;
use App\Support\HandlesRestrictedDeletes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FarmProductPresentationController extends Controller
{
    use HandlesRestrictedDeletes;

    public function index(): Response
    {
        $presentations = FarmProductPresentation::query()
            ->with([
                'farmProduct:id,farm_id,product_id',
                'farmProduct.farm:id,name',
                'farmProduct.product:id,name,variety_id,category,variety,color',
                'farmProduct.product.catalogVariety:id,flower_type_id,name,color',
                'farmProduct.product.catalogVariety.flowerType:id,name',
            ])
            ->orderByDesc('id')
            ->get()
            ->map(fn (FarmProductPresentation $presentation) => $this->transformPresentation($presentation));

        return Inertia::render('Admin/Presentations/Index', [
            'presentations' => $presentations,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Presentations/Create', [
            'farmProducts' => $this->farmProductOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        FarmProductPresentation::create($this->validatedData($request));

        return redirect()
            ->route('admin.presentations.index')
            ->with('success', 'Presentación creada correctamente.');
    }

    public function edit(FarmProductPresentation $presentation): Response
    {
        return Inertia::render('Admin/Presentations/Edit', [
            'presentation' => [
                'id' => $presentation->id,
                'farm_product_id' => $presentation->farm_product_id,
                'stem_length_cm' => $presentation->stem_length_cm,
                'stems_per_bunch' => $presentation->stems_per_bunch,
                'price_per_stem' => $presentation->price_per_stem,
                'price_per_bunch' => $presentation->price_per_bunch,
                'active' => (bool) $presentation->active,
            ],
            'farmProducts' => $this->farmProductOptions(),
        ]);
    }

    public function update(Request $request, FarmProductPresentation $presentation): RedirectResponse
    {
        $presentation->update($this->validatedData($request, $presentation));

        return redirect()
            ->route('admin.presentations.index')
            ->with('success', 'Presentación actualizada correctamente.');
    }

    public function destroy(FarmProductPresentation $presentation): RedirectResponse
    {
        return $this->deleteOrFailFriendly(
            fn () => $presentation->delete(),
            'admin.presentations.index',
            'Presentación eliminada correctamente.',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?FarmProductPresentation $presentation = null): array
    {
        $validated = $request->validate([
            'farm_product_id' => ['required', 'integer', 'exists:farm_products,id'],
            'stem_length_cm' => [
                'required',
                'integer',
                'min:20',
                'max:200',
                Rule::unique('farm_product_presentations')
                    ->where(fn ($query) => $query->where('farm_product_id', $request->integer('farm_product_id')))
                    ->ignore($presentation?->id),
            ],
            'stems_per_bunch' => ['nullable', 'integer', 'min:1'],
            'price_per_stem' => ['nullable', 'numeric', 'min:0'],
            'price_per_bunch' => ['nullable', 'numeric', 'min:0'],
            'active' => ['boolean'],
        ], [
            'stem_length_cm.unique' => 'Ya existe una presentación con la misma finca/producto y longitud.',
        ]);

        $validated['active'] = $request->boolean('active');

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    private function transformPresentation(FarmProductPresentation $presentation): array
    {
        $farmProduct = $presentation->farmProduct;
        $product = $farmProduct?->product;
        $relatedVariety = $product?->relationLoaded('catalogVariety')
            ? $product->getRelation('catalogVariety')
            : null;

        return [
            'id' => $presentation->id,
            'farm_name' => $farmProduct?->farm?->name,
            'product_name' => $product?->name,
            'variety_name' => $relatedVariety?->name
                ?? $product?->getAttributes()['variety']
                ?? null,
            'stem_length_cm' => $presentation->stem_length_cm,
            'stems_per_bunch' => $presentation->stems_per_bunch,
            'price_per_stem' => $presentation->price_per_stem,
            'price_per_bunch' => $presentation->price_per_bunch,
            'active' => (bool) $presentation->active,
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{id:int,label:string}>
     */
    private function farmProductOptions()
    {
        return FarmProduct::query()
            ->with([
                'farm:id,name',
                'product:id,name,variety_id,category,variety,color',
                'product.catalogVariety:id,flower_type_id,name,color',
                'product.catalogVariety.flowerType:id,name',
            ])
            ->where('active', true)
            ->orderByDesc('id')
            ->get()
            ->map(function (FarmProduct $farmProduct) {
                $product = $farmProduct->product;
                $relatedVariety = $product?->getRelation('catalogVariety');
                $flowerType = $relatedVariety?->flowerType?->name
                    ?? $product?->getAttributes()['category']
                    ?? 'Sin tipo';
                $varietyName = $relatedVariety?->name
                    ?? $product?->getAttributes()['variety']
                    ?? 'Sin variedad';
                $color = $relatedVariety?->color
                    ?? $product?->getAttributes()['color']
                    ?? 'Sin color';
                $farmName = $farmProduct->farm?->name ?? 'Sin finca';

                return [
                    'id' => $farmProduct->id,
                    'label' => "{$farmName} - {$flowerType} / {$varietyName} / {$color}",
                ];
            })
            ->values();
    }
}
