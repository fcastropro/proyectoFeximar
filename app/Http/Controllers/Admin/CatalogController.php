<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlowerType;
use App\Models\Variety;
use Illuminate\Http\JsonResponse;

class CatalogController extends Controller
{
    public function varieties(FlowerType $flowerType): JsonResponse
    {
        $varieties = Variety::query()
            ->where('flower_type_id', $flowerType->id)
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'color', 'flower_type_id']);

        return response()->json($varieties);
    }
}
