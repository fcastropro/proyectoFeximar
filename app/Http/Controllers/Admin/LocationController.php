<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\Province;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    public function provinces(Country $country): JsonResponse
    {
        $provinces = Province::query()
            ->where('country_id', $country->id)
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'country_id']);

        return response()->json($provinces);
    }

    public function cities(Province $province): JsonResponse
    {
        $cities = City::query()
            ->where('province_id', $province->id)
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'province_id']);

        return response()->json($cities);
    }
}
