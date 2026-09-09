<?php

namespace App\Http\Controllers\Farm;

use App\Models\FarmProductAvailability;
use App\Models\FarmProductPresentation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AvailabilityController extends BaseFarmController
{
    public function index(Request $request): Response
    {
        $farm = $this->currentFarm($request);

        $availabilities = FarmProductAvailability::query()
            ->with([
                'presentation:id,farm_product_id,stem_length_cm',
                'presentation.farmProduct:id,farm_id,product_id',
                'presentation.farmProduct.product:id,name,variety_id,variety,color,category',
                'presentation.farmProduct.product.catalogVariety:id,name,color,flower_type_id',
                'presentation.farmProduct.product.catalogVariety.flowerType:id,name',
            ])
            ->whereHas('presentation.farmProduct', fn ($q) => $q->where('farm_id', $farm->id))
            ->orderByDesc('year')
            ->orderByDesc('week_number')
            ->orderByDesc('id')
            ->get()
            ->map(fn (FarmProductAvailability $item) => $this->transform($item));

        return Inertia::render('Farm/Availabilities/Index', [
            'availabilities' => $availabilities,
        ]);
    }

    public function create(Request $request): Response
    {
        $farm = $this->currentFarm($request);

        return Inertia::render('Farm/Availabilities/Create', [
            'presentations' => $this->presentationOptions($farm->id),
            'defaultYear' => (int) now()->isoWeekYear(),
            'defaultWeek' => (int) now()->isoWeek(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $farm = $this->currentFarm($request);
        $data = $this->validated($request, $farm->id);

        FarmProductAvailability::create($data);

        return redirect()
            ->route('farm.availabilities.index')
            ->with('success', 'Disponibilidad registrada correctamente.');
    }

    public function edit(Request $request, FarmProductAvailability $availability): Response
    {
        $farm = $this->currentFarm($request);
        $this->assertOwned($availability, $farm->id);

        return Inertia::render('Farm/Availabilities/Edit', [
            'availability' => [
                'id' => $availability->id,
                'farm_product_presentation_id' => $availability->farm_product_presentation_id,
                'year' => $availability->year,
                'week_number' => $availability->week_number,
                'available_stems' => $availability->available_stems,
                'reserved_stems' => $availability->reserved_stems,
                'price_per_stem' => $availability->price_per_stem,
                'price_per_bunch' => $availability->price_per_bunch,
                'active' => (bool) $availability->active,
            ],
            'presentations' => $this->presentationOptions($farm->id, $availability->farm_product_presentation_id),
        ]);
    }

    public function update(Request $request, FarmProductAvailability $availability): RedirectResponse
    {
        $farm = $this->currentFarm($request);
        $this->assertOwned($availability, $farm->id);

        $availability->update($this->validated($request, $farm->id, $availability));

        return redirect()
            ->route('farm.availabilities.index')
            ->with('success', 'Disponibilidad actualizada correctamente.');
    }

    public function destroy(Request $request, FarmProductAvailability $availability): RedirectResponse
    {
        $farm = $this->currentFarm($request);
        $this->assertOwned($availability, $farm->id);

        $availability->delete();

        return redirect()
            ->route('farm.availabilities.index')
            ->with('success', 'Disponibilidad eliminada correctamente.');
    }

    private function assertOwned(FarmProductAvailability $availability, int $farmId): void
    {
        $owned = FarmProductAvailability::query()
            ->whereKey($availability->id)
            ->whereHas('presentation.farmProduct', fn ($q) => $q->where('farm_id', $farmId))
            ->exists();

        abort_unless($owned, 403, 'No puedes gestionar disponibilidades de otra finca.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, int $farmId, ?FarmProductAvailability $availability = null): array
    {
        $validated = $request->validate([
            'farm_product_presentation_id' => [
                'required',
                'integer',
                Rule::exists('farm_product_presentations', 'id')->where(function ($query) use ($farmId) {
                    $query->whereIn('farm_product_id', function ($sub) use ($farmId) {
                        $sub->select('id')->from('farm_products')->where('farm_id', $farmId);
                    });
                }),
            ],
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
            'farm_product_presentation_id.exists' => 'La presentación no pertenece a tu finca.',
        ]);

        $validated['active'] = $request->boolean('active');

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(FarmProductAvailability $availability): array
    {
        $presentation = $availability->presentation;
        $product = $presentation?->farmProduct?->product;
        $variety = $product?->relationLoaded('catalogVariety') ? $product->getRelation('catalogVariety') : null;

        return [
            'id' => $availability->id,
            'year' => $availability->year,
            'week_number' => $availability->week_number,
            'product_name' => $product?->name,
            'variety_name' => $variety?->name ?? $product?->getAttributes()['variety'] ?? null,
            'stem_length_cm' => $presentation?->stem_length_cm,
            'available_stems' => $availability->available_stems,
            'reserved_stems' => $availability->reserved_stems,
            'remaining_stems' => $availability->remainingStems(),
            'price_per_stem' => $availability->price_per_stem,
            'price_per_bunch' => $availability->price_per_bunch,
            'active' => (bool) $availability->active,
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{id:int,label:string}>
     */
    private function presentationOptions(int $farmId, ?int $includeId = null)
    {
        return FarmProductPresentation::query()
            ->with([
                'farmProduct:id,farm_id,product_id',
                'farmProduct.product:id,name,variety_id,variety,color,category',
                'farmProduct.product.catalogVariety:id,name,color,flower_type_id',
                'farmProduct.product.catalogVariety.flowerType:id,name',
            ])
            ->whereHas('farmProduct', fn ($q) => $q->where('farm_id', $farmId))
            ->where(function ($query) use ($includeId) {
                $query->where('active', true);
                if ($includeId) {
                    $query->orWhere('id', $includeId);
                }
            })
            ->orderByDesc('id')
            ->get()
            ->map(function (FarmProductPresentation $presentation) {
                $product = $presentation->farmProduct?->product;
                $variety = $product?->getRelation('catalogVariety');
                $flowerType = $variety?->flowerType?->name
                    ?? $product?->getAttributes()['category']
                    ?? 'Sin tipo';
                $varietyName = $variety?->name
                    ?? $product?->getAttributes()['variety']
                    ?? 'Sin variedad';
                $color = $product?->getAttributes()['color']
                    ?? 'Sin color';

                return [
                    'id' => $presentation->id,
                    'label' => "{$flowerType} / {$varietyName} / {$color} - {$presentation->stem_length_cm} cm",
                ];
            })
            ->values();
    }
}
