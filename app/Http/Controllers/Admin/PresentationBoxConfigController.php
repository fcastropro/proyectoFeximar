<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BoxType;
use App\Models\FarmProductPresentation;
use App\Models\PresentationBoxConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PresentationBoxConfigController extends Controller
{
    public function index(): Response
    {
        $boxConfigs = PresentationBoxConfig::query()
            ->with([
                'boxType:id,code,name',
                'presentation:id,farm_product_id,stem_length_cm',
                'presentation.farmProduct:id,farm_id,product_id',
                'presentation.farmProduct.farm:id,name',
                'presentation.farmProduct.product:id,name,variety_id,category,variety,color',
                'presentation.farmProduct.product.variety:id,flower_type_id,name,color',
                'presentation.farmProduct.product.variety.flowerType:id,name',
            ])
            ->orderByDesc('id')
            ->get()
            ->map(fn (PresentationBoxConfig $config) => $this->transformConfig($config));

        return Inertia::render('Admin/BoxConfigs/Index', [
            'boxConfigs' => $boxConfigs,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/BoxConfigs/Create', [
            'presentations' => $this->presentationOptions(),
            'boxTypes' => $this->activeBoxTypes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        PresentationBoxConfig::create($this->validatedData($request));

        return redirect()
            ->route('admin.box-configs.index')
            ->with('success', 'Configuración de caja creada correctamente.');
    }

    public function edit(PresentationBoxConfig $boxConfig): Response
    {
        return Inertia::render('Admin/BoxConfigs/Edit', [
            'boxConfig' => [
                'id' => $boxConfig->id,
                'farm_product_presentation_id' => $boxConfig->farm_product_presentation_id,
                'box_type_id' => $boxConfig->box_type_id,
                'stems_per_box' => $boxConfig->stems_per_box,
                'bunches_per_box' => $boxConfig->bunches_per_box,
                'active' => (bool) $boxConfig->active,
            ],
            'presentations' => $this->presentationOptions($boxConfig->farm_product_presentation_id),
            'boxTypes' => $this->activeBoxTypes(),
        ]);
    }

    public function update(Request $request, PresentationBoxConfig $boxConfig): RedirectResponse
    {
        $boxConfig->update($this->validatedData($request, $boxConfig));

        return redirect()
            ->route('admin.box-configs.index')
            ->with('success', 'Configuración de caja actualizada correctamente.');
    }

    public function destroy(PresentationBoxConfig $boxConfig): RedirectResponse
    {
        $boxConfig->delete();

        return redirect()
            ->route('admin.box-configs.index')
            ->with('success', 'Configuración de caja eliminada correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?PresentationBoxConfig $boxConfig = null): array
    {
        $validated = $request->validate([
            'farm_product_presentation_id' => [
                'required',
                'integer',
                'exists:farm_product_presentations,id',
            ],
            'box_type_id' => [
                'required',
                'integer',
                'exists:box_types,id',
                Rule::unique('presentation_box_configs', 'box_type_id')
                    ->where(fn ($query) => $query->where(
                        'farm_product_presentation_id',
                        $request->integer('farm_product_presentation_id')
                    ))
                    ->ignore($boxConfig?->id),
            ],
            'stems_per_box' => ['required', 'integer', 'min:1'],
            'bunches_per_box' => ['nullable', 'integer', 'min:1'],
            'active' => ['boolean'],
        ], [
            'box_type_id.unique' => 'Ya existe una configuración para esa presentación y tipo de caja.',
        ]);

        $validated['active'] = $request->boolean('active');
        $validated['bunches_per_box'] = $validated['bunches_per_box'] ?? null;

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    private function transformConfig(PresentationBoxConfig $config): array
    {
        $meta = $this->presentationMeta($config->presentation);

        return [
            'id' => $config->id,
            'farm_name' => $meta['farm_name'],
            'product_name' => $meta['product_name'],
            'variety_name' => $meta['variety_name'],
            'stem_length_cm' => $meta['stem_length_cm'],
            'box_type' => $config->boxType?->code ?? $config->boxType?->name,
            'stems_per_box' => $config->stems_per_box,
            'bunches_per_box' => $config->bunches_per_box,
            'active' => (bool) $config->active,
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{id:int,label:string}>
     */
    private function presentationOptions(?int $includeId = null)
    {
        return FarmProductPresentation::query()
            ->with([
                'farmProduct:id,farm_id,product_id',
                'farmProduct.farm:id,name',
                'farmProduct.product:id,name,variety_id,category,variety,color',
                'farmProduct.product.variety:id,flower_type_id,name,color',
                'farmProduct.product.variety.flowerType:id,name',
            ])
            ->where(function ($query) use ($includeId) {
                $query->where('active', true);

                if ($includeId) {
                    $query->orWhere('id', $includeId);
                }
            })
            ->orderByDesc('id')
            ->get()
            ->map(function (FarmProductPresentation $presentation) {
                $meta = $this->presentationMeta($presentation);

                return [
                    'id' => $presentation->id,
                    'label' => sprintf(
                        '%s - %s / %s / %s - %s cm',
                        $meta['farm_name'],
                        $meta['flower_type'],
                        $meta['variety_name'],
                        $meta['color'],
                        $meta['stem_length_cm']
                    ),
                ];
            })
            ->values();
    }

    /**
     * @return array{farm_name:string,product_name:string,flower_type:string,variety_name:string,color:string,stem_length_cm:int|string}
     */
    private function presentationMeta(?FarmProductPresentation $presentation): array
    {
        $farmProduct = $presentation?->farmProduct;
        $product = $farmProduct?->product;
        $relatedVariety = $product?->relationLoaded('variety')
            ? $product->getRelation('variety')
            : null;

        return [
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
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{id:int,code:string,name:string}>
     */
    private function activeBoxTypes()
    {
        return BoxType::query()
            ->where('active', true)
            ->orderBy('id')
            ->get(['id', 'code', 'name']);
    }
}
