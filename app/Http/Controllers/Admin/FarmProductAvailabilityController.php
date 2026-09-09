<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FarmProductAvailability;
use App\Models\FarmProductPresentation;
use App\Support\HandlesRestrictedDeletes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FarmProductAvailabilityController extends Controller
{
    use HandlesRestrictedDeletes;

    public function index(): Response
    {
        $availabilities = FarmProductAvailability::query()
            ->with([
                'presentation:id,farm_product_id,stem_length_cm',
                'presentation.farmProduct:id,farm_id,product_id',
                'presentation.farmProduct.farm:id,name',
                'presentation.farmProduct.product:id,name,variety_id,category,variety,color',
                'presentation.farmProduct.product.catalogVariety:id,flower_type_id,name,color',
                'presentation.farmProduct.product.catalogVariety.flowerType:id,name',
            ])
            ->orderByDesc('year')
            ->orderByDesc('week_number')
            ->orderByDesc('id')
            ->get()
            ->map(fn (FarmProductAvailability $availability) => $this->transformAvailability($availability));

        return Inertia::render('Admin/Availabilities/Index', [
            'availabilities' => $availabilities,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Availabilities/Create', [
            'presentations' => $this->presentationOptions(),
            'defaultYear' => (int) now()->isoWeekYear(),
            'defaultWeek' => (int) now()->isoWeek(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        FarmProductAvailability::create($this->validatedData($request));

        return redirect()
            ->route('admin.availabilities.index')
            ->with('success', 'Disponibilidad semanal registrada correctamente.');
    }

    public function edit(FarmProductAvailability $availability): Response
    {
        return Inertia::render('Admin/Availabilities/Edit', [
            'availability' => [
                'id' => $availability->id,
                'farm_product_presentation_id' => $availability->farm_product_presentation_id,
                'year' => $availability->year,
                'week_number' => $availability->week_number,
                'available_stems' => $availability->available_stems,
                'price_per_stem' => $availability->price_per_stem,
                'price_per_bunch' => $availability->price_per_bunch,
                'active' => (bool) $availability->active,
            ],
            'presentations' => $this->presentationOptions(),
        ]);
    }

    public function update(Request $request, FarmProductAvailability $availability): RedirectResponse
    {
        $availability->update($this->validatedData($request, $availability));

        return redirect()
            ->route('admin.availabilities.index')
            ->with('success', 'Disponibilidad semanal actualizada correctamente.');
    }

    public function destroy(FarmProductAvailability $availability): RedirectResponse
    {
        return $this->deleteOrFailFriendly(
            fn () => $availability->delete(),
            'admin.availabilities.index',
            'Disponibilidad semanal eliminada correctamente.',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?FarmProductAvailability $availability = null): array
    {
        $validated = $request->validate([
            'farm_product_presentation_id' => ['required', 'integer', 'exists:farm_product_presentations,id'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'week_number' => [
                'required',
                'integer',
                'min:1',
                'max:53',
                Rule::unique('farm_product_availabilities')
                    ->where(fn ($query) => $query
                        ->where('farm_product_presentation_id', $request->integer('farm_product_presentation_id'))
                        ->where('year', $request->integer('year')))
                    ->ignore($availability?->id),
            ],
            'available_stems' => ['required', 'integer', 'min:0'],
            'price_per_stem' => ['nullable', 'numeric', 'min:0'],
            'price_per_bunch' => ['nullable', 'numeric', 'min:0'],
            'active' => ['boolean'],
        ], [
            'week_number.unique' => 'Ya existe disponibilidad para esa presentación en la misma semana y año.',
        ]);

        $validated['active'] = $request->boolean('active');

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    private function transformAvailability(FarmProductAvailability $availability): array
    {
        $presentation = $availability->presentation;
        $farmProduct = $presentation?->farmProduct;
        $product = $farmProduct?->product;
        $relatedVariety = $product?->relationLoaded('catalogVariety')
            ? $product->getRelation('catalogVariety')
            : null;

        return [
            'id' => $availability->id,
            'farm_name' => $farmProduct?->farm?->name,
            'product_name' => $product?->name,
            'variety_name' => $relatedVariety?->name
                ?? $product?->getAttributes()['variety']
                ?? null,
            'stem_length_cm' => $presentation?->stem_length_cm,
            'year' => $availability->year,
            'week_number' => $availability->week_number,
            'available_stems' => $availability->available_stems,
            'price_per_stem' => $availability->price_per_stem,
            'price_per_bunch' => $availability->price_per_bunch,
            'active' => (bool) $availability->active,
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{id:int,label:string,base_price_per_stem:?string,base_price_per_bunch:?string}>
     */
    private function presentationOptions()
    {
        return FarmProductPresentation::query()
            ->with([
                'farmProduct:id,farm_id,product_id',
                'farmProduct.farm:id,name',
                'farmProduct.product:id,name,variety_id,category,variety,color',
                'farmProduct.product.catalogVariety:id,flower_type_id,name,color',
                'farmProduct.product.catalogVariety.flowerType:id,name',
            ])
            ->where('active', true)
            ->orderByDesc('id')
            ->get()
            ->map(function (FarmProductPresentation $presentation) {
                $farmProduct = $presentation->farmProduct;
                $product = $farmProduct?->product;
                $relatedVariety = $product?->getRelation('catalogVariety');
                $flowerType = $relatedVariety?->flowerType?->name
                    ?? $product?->getAttributes()['category']
                    ?? 'Sin tipo';
                $varietyName = $relatedVariety?->name
                    ?? $product?->getAttributes()['variety']
                    ?? 'Sin variedad';
                $color = $product?->getAttributes()['color']
                    ?? 'Sin color';
                $farmName = $farmProduct?->farm?->name ?? 'Sin finca';

                return [
                    'id' => $presentation->id,
                    'label' => "{$farmName} - {$flowerType} / {$varietyName} / {$color} - {$presentation->stem_length_cm} cm",
                    'base_price_per_stem' => $presentation->price_per_stem,
                    'base_price_per_bunch' => $presentation->price_per_bunch,
                ];
            })
            ->values();
    }
}
