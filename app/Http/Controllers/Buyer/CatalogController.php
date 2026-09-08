<?php

namespace App\Http\Controllers\Buyer;

use App\Models\Farm;
use App\Models\FarmProductAvailability;
use App\Models\FlowerType;
use App\Models\Variety;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends BaseBuyerController
{
    public function index(Request $request): Response
    {
        $now = Carbon::now();
        $year = (int) $request->input('year', $now->isoWeekYear());
        $week = (int) $request->input('week', $now->isoWeek());

        $query = FarmProductAvailability::query()
            ->with([
                'presentation:id,farm_product_id,stem_length_cm,stems_per_bunch',
                'presentation.farmProduct:id,farm_id,product_id',
                'presentation.farmProduct.farm:id,name',
                'presentation.farmProduct.product:id,name,variety_id,category,variety,color,image_path',
                'presentation.farmProduct.product.variety:id,flower_type_id,name,color',
                'presentation.farmProduct.product.variety.flowerType:id,name',
            ])
            ->where('active', true)
            ->where('year', $year)
            ->where('week_number', $week)
            ->whereRaw('(available_stems - reserved_stems) > 0')
            ->whereHas('presentation', fn ($q) => $q->where('stems_per_bunch', '>', 0));

        if ($request->filled('flower_type_id')) {
            $query->whereHas('presentation.farmProduct.product.variety', function ($q) use ($request) {
                $q->where('flower_type_id', $request->integer('flower_type_id'));
            });
        }

        if ($request->filled('variety_id')) {
            $query->whereHas('presentation.farmProduct.product', function ($q) use ($request) {
                $q->where('variety_id', $request->integer('variety_id'));
            });
        }

        if ($request->filled('farm_id')) {
            $query->whereHas('presentation.farmProduct', function ($q) use ($request) {
                $q->where('farm_id', $request->integer('farm_id'));
            });
        }

        if ($request->filled('stem_length_cm')) {
            $query->whereHas('presentation', function ($q) use ($request) {
                $q->where('stem_length_cm', $request->integer('stem_length_cm'));
            });
        }

        if ($request->filled('search')) {
            $search = '%'.$request->string('search').'%';
            $query->whereHas('presentation.farmProduct.product', function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('variety', 'like', $search)
                    ->orWhereHas('variety', fn ($vq) => $vq->where('name', 'like', $search));
            });
        }

        $items = $query->orderByDesc('id')->get()->map(function (FarmProductAvailability $availability) {
            $presentation = $availability->presentation;
            $farmProduct = $presentation?->farmProduct;
            $product = $farmProduct?->product;
            $variety = $product?->relationLoaded('variety') ? $product->getRelation('variety') : null;
            $pricePerStem = $availability->price_per_stem !== null ? (float) $availability->price_per_stem : null;
            $stemsPerBunch = (int) ($presentation?->stems_per_bunch ?? 0);

            return [
                'availability_id' => $availability->id,
                'product_name' => $product?->name,
                'flower_type' => $variety?->flowerType?->name ?? ($product?->getAttributes()['category'] ?? null),
                'variety' => $variety?->name ?? ($product?->getAttributes()['variety'] ?? null),
                'farm_name' => $farmProduct?->farm?->name,
                'stem_length_cm' => $presentation?->stem_length_cm,
                'year' => $availability->year,
                'week_number' => $availability->week_number,
                'effective_stems' => $availability->remainingStems(),
                'stems_per_bunch' => $stemsPerBunch,
                'price_per_stem' => $pricePerStem,
                'image_url' => $product?->imageUrl(),
            ];
        })->values();

        return Inertia::render('Buyer/Catalog/Index', [
            'items' => $items,
            'filters' => [
                'year' => $year,
                'week' => $week,
                'flower_type_id' => $request->input('flower_type_id'),
                'variety_id' => $request->input('variety_id'),
                'farm_id' => $request->input('farm_id'),
                'stem_length_cm' => $request->input('stem_length_cm'),
                'search' => $request->input('search'),
            ],
            'filterOptions' => [
                'flower_types' => FlowerType::query()->where('active', true)->orderBy('name')->get(['id', 'name']),
                'varieties' => Variety::query()->where('active', true)->orderBy('name')->get(['id', 'name', 'flower_type_id']),
                'farms' => Farm::query()->where('active', true)->orderBy('name')->get(['id', 'name']),
                'stem_lengths' => \App\Models\FarmProductPresentation::query()
                    ->select('stem_length_cm')
                    ->distinct()
                    ->orderBy('stem_length_cm')
                    ->pluck('stem_length_cm'),
            ],
        ]);
    }
}
