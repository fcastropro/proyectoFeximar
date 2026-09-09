<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CargoAgency;
use App\Support\HandlesRestrictedDeletes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CargoAgencyController extends Controller
{
    use HandlesRestrictedDeletes;

    public function index(): Response
    {
        return Inertia::render('Admin/CargoAgencies/Index', [
            'agencies' => CargoAgency::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/CargoAgencies/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        CargoAgency::query()->create($this->validated($request));

        return redirect()
            ->route('admin.cargo-agencies.index')
            ->with('success', 'Agencia de carga creada.');
    }

    public function edit(CargoAgency $cargoAgency): Response
    {
        return Inertia::render('Admin/CargoAgencies/Edit', [
            'agency' => $cargoAgency,
        ]);
    }

    public function update(Request $request, CargoAgency $cargoAgency): RedirectResponse
    {
        $cargoAgency->update($this->validated($request));

        return redirect()
            ->route('admin.cargo-agencies.index')
            ->with('success', 'Agencia de carga actualizada.');
    }

    public function destroy(CargoAgency $cargoAgency): RedirectResponse
    {
        return $this->deleteOrFailFriendly(
            fn () => $cargoAgency->delete(),
            'admin.cargo-agencies.index',
            'Agencia de carga eliminada.',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'active' => ['boolean'],
        ]);

        $validated['active'] = $request->boolean('active', true);

        return $validated;
    }
}
