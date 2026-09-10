<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlowerType;
use App\Models\Variety;
use App\Support\HandlesRestrictedDeletes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class VarietyController extends Controller
{
    use HandlesRestrictedDeletes;

    public function index(): Response
    {
        $varieties = Variety::query()
            ->with('flowerType:id,name')
            ->join('flower_types', 'flower_types.id', '=', 'varieties.flower_type_id')
            ->orderBy('flower_types.name')
            ->orderBy('varieties.name')
            ->select('varieties.*')
            ->get()
            ->map(fn (Variety $variety) => [
                'id' => $variety->id,
                'flower_type' => $variety->flowerType?->name,
                'name' => $variety->name,
                'color' => $variety->color,
                'active' => (bool) $variety->active,
            ]);

        return Inertia::render('Admin/Varieties/Index', [
            'varieties' => $varieties,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Varieties/Create', [
            'flowerTypes' => $this->activeFlowerTypes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Variety::query()->create($this->validated($request));

        return redirect()
            ->route('admin.varieties.index')
            ->with('success', 'Variedad creada.');
    }

    public function edit(Variety $variety): Response
    {
        return Inertia::render('Admin/Varieties/Edit', [
            'variety' => [
                'id' => $variety->id,
                'flower_type_id' => $variety->flower_type_id,
                'name' => $variety->name,
                'color' => $variety->color,
                'active' => (bool) $variety->active,
            ],
            'flowerTypes' => $this->activeFlowerTypes(),
        ]);
    }

    public function update(Request $request, Variety $variety): RedirectResponse
    {
        $variety->update($this->validated($request, $variety));

        return redirect()
            ->route('admin.varieties.index')
            ->with('success', 'Variedad actualizada.');
    }

    public function destroy(Variety $variety): RedirectResponse
    {
        if ($variety->products()->exists()) {
            return redirect()
                ->route('admin.varieties.index')
                ->with('error', 'No se puede eliminar esta variedad porque está siendo utilizada en productos.');
        }

        return $this->deleteOrFailFriendly(
            fn () => $variety->delete(),
            'admin.varieties.index',
            'Variedad eliminada.',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Variety $variety = null): array
    {
        $uniqueName = Rule::unique('varieties', 'name')
            ->where(fn ($query) => $query->where('flower_type_id', $request->integer('flower_type_id')));

        if ($variety) {
            $uniqueName = $uniqueName->ignore($variety->id);
        }

        $validated = $request->validate([
            'flower_type_id' => ['required', 'integer', 'exists:flower_types,id'],
            'name' => ['required', 'string', 'max:255', $uniqueName],
            'color' => ['required', 'string', 'max:100'],
            'active' => ['boolean'],
        ]);

        $validated['active'] = $request->boolean('active', true);

        return $validated;
    }

    /**
     * @return \Illuminate\Support\Collection<int, FlowerType>
     */
    private function activeFlowerTypes()
    {
        return FlowerType::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
