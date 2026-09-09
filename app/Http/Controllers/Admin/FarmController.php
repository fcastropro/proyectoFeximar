<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\Farm;
use App\Models\Province;
use App\Support\HandlesRestrictedDeletes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FarmController extends Controller
{
    use HandlesRestrictedDeletes;

    public function index(): Response
    {
        return Inertia::render('Admin/Farms/Index', [
            'farms' => Farm::query()
                ->with([
                    'city:id,name',
                    'province:id,name',
                ])
                ->orderByDesc('id')
                ->get([
                    'id',
                    'name',
                    'commercial_name',
                    'ruc',
                    'city_id',
                    'province_id',
                    'active',
                ]),
        ]);
    }

    public function create(): Response
    {
        $ecuador = Country::query()
            ->where('iso2', 'EC')
            ->where('active', true)
            ->first(['id', 'name', 'iso2']);

        return Inertia::render('Admin/Farms/Create', [
            'countries' => $this->activeCountries(),
            'defaultCountryId' => $ecuador?->id,
            'initialProvinces' => $ecuador
                ? $this->provincesForCountry($ecuador->id)
                : [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Farm::create($this->validatedData($request));

        return redirect()
            ->route('admin.farms.index')
            ->with('success', 'Finca creada correctamente.');
    }

    public function edit(Farm $farm): Response
    {
        $farm->loadMissing(['country:id,name,iso2', 'province:id,name', 'city:id,name']);

        return Inertia::render('Admin/Farms/Edit', [
            'farm' => $farm->only([
                'id',
                'name',
                'commercial_name',
                'ruc',
                'email',
                'phone',
                'country_id',
                'province_id',
                'city_id',
                'address',
                'description',
                'active',
            ]),
            'countries' => $this->activeCountries(),
            'initialProvinces' => $farm->country_id
                ? $this->provincesForCountry($farm->country_id)
                : [],
            'initialCities' => $farm->province_id
                ? $this->citiesForProvince($farm->province_id)
                : [],
        ]);
    }

    public function update(Request $request, Farm $farm): RedirectResponse
    {
        $farm->update($this->validatedData($request, $farm));

        return redirect()
            ->route('admin.farms.index')
            ->with('success', 'Finca actualizada correctamente.');
    }

    public function destroy(Farm $farm): RedirectResponse
    {
        if ($farm->farmProducts()->exists() || $farm->fulfillments()->exists()) {
            return redirect()
                ->route('admin.farms.index')
                ->with(
                    'error',
                    'No se puede eliminar este registro porque tiene información relacionada.'
                );
        }

        return $this->deleteOrFailFriendly(
            fn () => $farm->delete(),
            'admin.farms.index',
            'Finca eliminada correctamente.',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?Farm $farm = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'commercial_name' => ['nullable', 'string', 'max:255'],
            'ruc' => [
                'nullable',
                'string',
                'max:13',
                Rule::unique('farms', 'ruc')->ignore($farm?->id),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'province_id' => [
                'required',
                'integer',
                Rule::exists('provinces', 'id')->where(
                    fn ($query) => $query->where('country_id', $request->integer('country_id'))
                ),
            ],
            'city_id' => [
                'required',
                'integer',
                Rule::exists('cities', 'id')->where(
                    fn ($query) => $query->where('province_id', $request->integer('province_id'))
                ),
            ],
            'address' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'active' => ['boolean'],
        ]);

        $validated['active'] = $request->boolean('active');

        return $validated;
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

    /**
     * @return \Illuminate\Support\Collection<int, array{id:int,name:string,country_id:int}>
     */
    private function provincesForCountry(int $countryId)
    {
        return Province::query()
            ->where('country_id', $countryId)
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'country_id']);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{id:int,name:string,province_id:int}>
     */
    private function citiesForProvince(int $provinceId)
    {
        return City::query()
            ->where('province_id', $provinceId)
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'province_id']);
    }
}
