<?php

namespace App\Http\Controllers\Farm;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends BaseFarmController
{
    public function show(Request $request): Response
    {
        $farm = $this->currentFarm($request);
        $farm->load(['country:id,name', 'province:id,name', 'city:id,name']);

        return Inertia::render('Farm/Profile', [
            'farm' => [
                'id' => $farm->id,
                'name' => $farm->name,
                'commercial_name' => $farm->commercial_name,
                'ruc' => $farm->ruc,
                'email' => $farm->email,
                'phone' => $farm->phone,
                'address' => $farm->address,
                'description' => $farm->description,
                'country' => $farm->country?->name,
                'province' => $farm->province?->name,
                'city' => $farm->city?->name,
                'active' => (bool) $farm->active,
            ],
        ]);
    }
}
