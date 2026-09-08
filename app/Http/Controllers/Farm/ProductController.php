<?php

namespace App\Http\Controllers\Farm;

use App\Models\FarmProduct;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends BaseFarmController
{
    public function index(Request $request): Response
    {
        $farm = $this->currentFarm($request);

        $products = FarmProduct::query()
            ->with([
                'product:id,name,variety_id,category,variety,color',
                'product.variety:id,flower_type_id,name,color',
                'product.variety.flowerType:id,name',
                'presentations:id,farm_product_id,stem_length_cm,active',
            ])
            ->where('farm_id', $farm->id)
            ->orderByDesc('id')
            ->get()
            ->map(function (FarmProduct $farmProduct) {
                $product = $farmProduct->product;
                $variety = $product?->relationLoaded('variety') ? $product->getRelation('variety') : null;

                return [
                    'id' => $farmProduct->id,
                    'product_name' => $product?->name,
                    'variety_name' => $variety?->name ?? $product?->getAttributes()['variety'] ?? null,
                    'flower_type' => $variety?->flowerType?->name ?? $product?->getAttributes()['category'] ?? null,
                    'color' => $variety?->color ?? $product?->getAttributes()['color'] ?? null,
                    'active' => (bool) $farmProduct->active,
                    'presentations' => $farmProduct->presentations
                        ->where('active', true)
                        ->map(fn ($p) => [
                            'id' => $p->id,
                            'stem_length_cm' => $p->stem_length_cm,
                        ])
                        ->values(),
                ];
            });

        return Inertia::render('Farm/Products/Index', [
            'products' => $products,
        ]);
    }
}
