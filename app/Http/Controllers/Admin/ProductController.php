<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlowerType;
use App\Models\Product;
use App\Models\Variety;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(): Response
    {
        $products = Product::query()
            ->with([
                'variety:id,flower_type_id,name,color',
                'variety.flowerType:id,name',
            ])
            ->orderByDesc('id')
            ->get([
                'id',
                'name',
                'variety_id',
                'category',
                'variety',
                'color',
                'image_path',
                'active',
            ])
            ->map(function (Product $product) {
                $attributes = $product->getAttributes();
                $relatedVariety = $product->getRelation('variety');

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'flower_type' => $relatedVariety?->flowerType?->name
                        ?? ($attributes['category'] ?? null),
                    'variety_name' => $relatedVariety?->name
                        ?? ($attributes['variety'] ?? null),
                    'color' => $relatedVariety?->color
                        ?? ($attributes['color'] ?? null),
                    'image_url' => $product->imageUrl(),
                    'active' => $product->active,
                ];
            });

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Products/Create', [
            'flowerTypes' => $this->activeFlowerTypes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        Product::create($data);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Producto creado correctamente.');
    }

    public function edit(Product $product): Response
    {
        $product->loadMissing([
            'variety:id,flower_type_id,name,color',
            'variety.flowerType:id,name',
        ]);

        $relatedVariety = $product->getRelation('variety');

        return Inertia::render('Admin/Products/Edit', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'flower_type_id' => $relatedVariety?->flower_type_id,
                'variety_id' => $product->variety_id,
                'color' => ($product->getAttributes()['color'] ?? null) ?: $relatedVariety?->color,
                'description' => $product->description,
                'image_url' => $product->imageUrl(),
                'active' => $product->active,
            ],
            'flowerTypes' => $this->activeFlowerTypes(),
            'initialVarieties' => $relatedVariety?->flower_type_id
                ? $this->varietiesForFlowerType($relatedVariety->flower_type_id)
                : [],
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validatedData($request);
        $previousPath = $product->image_path;

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

        if ($request->hasFile('image') && $previousPath && $previousPath !== $product->image_path) {
            Storage::disk('public')->delete($previousPath);
        }

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $path = $product->image_path;
        $product->delete();

        if ($path) {
            Storage::disk('public')->delete($path);
        }

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Producto eliminado correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'flower_type_id' => ['required', 'integer', 'exists:flower_types,id'],
            'variety_id' => [
                'required',
                'integer',
                Rule::exists('varieties', 'id')->where(
                    fn ($query) => $query->where('flower_type_id', $request->integer('flower_type_id'))
                ),
            ],
            'color' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'active' => ['boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        $variety = Variety::query()
            ->with('flowerType:id,name')
            ->findOrFail($validated['variety_id']);

        return [
            'name' => $validated['name'],
            'variety_id' => $variety->id,
            'category' => $variety->flowerType?->name,
            'variety' => $variety->name,
            'color' => $validated['color'] ?: $variety->color,
            'description' => $validated['description'] ?? null,
            'active' => $request->boolean('active'),
        ];
    }

    private function activeFlowerTypes()
    {
        return FlowerType::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function varietiesForFlowerType(int $flowerTypeId)
    {
        return Variety::query()
            ->where('flower_type_id', $flowerTypeId)
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'color', 'flower_type_id']);
    }
}
