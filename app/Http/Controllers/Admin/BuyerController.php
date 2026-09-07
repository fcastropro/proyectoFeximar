<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\Country;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BuyerController extends Controller
{
    public function index(): Response
    {
        $buyers = Buyer::query()
            ->with('country:id,name')
            ->orderByDesc('id')
            ->get([
                'id',
                'company_name',
                'contact_name',
                'email',
                'phone',
                'country_id',
                'city',
                'active',
            ])
            ->map(function (Buyer $buyer) {
                return [
                    'id' => $buyer->id,
                    'company_name' => $buyer->company_name,
                    'contact_name' => $buyer->contact_name,
                    'email' => $buyer->email,
                    'phone' => $buyer->phone,
                    'country' => $buyer->country?->name,
                    'city' => $buyer->city,
                    'active' => $buyer->active,
                ];
            });

        return Inertia::render('Admin/Buyers/Index', [
            'buyers' => $buyers,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Buyers/Create', [
            'countries' => $this->activeCountries(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Buyer::create($this->validatedData($request));

        return redirect()
            ->route('admin.buyers.index')
            ->with('success', 'Comprador creado correctamente.');
    }

    public function edit(Buyer $buyer): Response
    {
        return Inertia::render('Admin/Buyers/Edit', [
            'buyer' => $buyer->only([
                'id',
                'company_name',
                'contact_name',
                'email',
                'phone',
                'country_id',
                'city',
                'address',
                'active',
            ]),
            'countries' => $this->activeCountries(),
        ]);
    }

    public function update(Request $request, Buyer $buyer): RedirectResponse
    {
        $buyer->update($this->validatedData($request));

        return redirect()
            ->route('admin.buyers.index')
            ->with('success', 'Comprador actualizado correctamente.');
    }

    public function destroy(Buyer $buyer): RedirectResponse
    {
        $buyer->delete();

        return redirect()
            ->route('admin.buyers.index')
            ->with('success', 'Comprador eliminado correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'city' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'active' => ['boolean'],
        ]);

        $country = Country::query()->findOrFail($validated['country_id']);

        // Dual-write temporal: la columna string country sigue NOT NULL.
        return [
            'company_name' => $validated['company_name'],
            'contact_name' => $validated['contact_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'country_id' => $country->id,
            'country' => $country->name,
            'city' => $validated['city'] ?? null,
            'address' => $validated['address'] ?? null,
            'active' => $request->boolean('active'),
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{id:int,name:string,iso2:string}>
     */
    private function activeCountries()
    {
        return Country::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'iso2']);
    }
}
