<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Farm;
use App\Models\FarmUser;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FarmUserController extends Controller
{
    public function index(): Response
    {
        $farmUsers = FarmUser::query()
            ->with(['farm:id,name', 'user:id,name,email'])
            ->orderByDesc('id')
            ->get()
            ->map(fn (FarmUser $item) => [
                'id' => $item->id,
                'farm_id' => $item->farm_id,
                'farm_name' => $item->farm?->name,
                'user_id' => $item->user_id,
                'user_name' => $item->user?->name,
                'user_email' => $item->user?->email,
                'role' => $item->role,
                'active' => (bool) $item->active,
            ]);

        return Inertia::render('Admin/FarmUsers/Index', [
            'farmUsers' => $farmUsers,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/FarmUsers/Create', [
            'farms' => Farm::query()->where('active', true)->orderBy('name')->get(['id', 'name']),
            'roles' => FarmUser::ROLES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'farm_id' => ['required', 'integer', 'exists:farms,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', Rule::in(FarmUser::ROLES)],
            'active' => ['boolean'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        FarmUser::create([
            'farm_id' => $validated['farm_id'],
            'user_id' => $user->id,
            'role' => $validated['role'],
            'active' => $request->boolean('active', true),
        ]);

        return redirect()
            ->route('admin.farm-users.index')
            ->with('success', 'Usuario de finca creado correctamente.');
    }

    public function edit(FarmUser $farmUser): Response
    {
        $farmUser->load(['user:id,name,email', 'farm:id,name']);

        return Inertia::render('Admin/FarmUsers/Edit', [
            'farmUser' => [
                'id' => $farmUser->id,
                'farm_id' => $farmUser->farm_id,
                'user_name' => $farmUser->user?->name,
                'user_email' => $farmUser->user?->email,
                'role' => $farmUser->role,
                'active' => (bool) $farmUser->active,
            ],
            'farms' => Farm::query()->where('active', true)->orderBy('name')->get(['id', 'name']),
            'roles' => FarmUser::ROLES,
        ]);
    }

    public function update(Request $request, FarmUser $farmUser): RedirectResponse
    {
        $validated = $request->validate([
            'farm_id' => ['required', 'integer', 'exists:farms,id'],
            'role' => ['required', 'string', Rule::in(FarmUser::ROLES)],
            'active' => ['boolean'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $farmUser->update([
            'farm_id' => $validated['farm_id'],
            'role' => $validated['role'],
            'active' => $request->boolean('active'),
        ]);

        if (! empty($validated['password'])) {
            $farmUser->user?->update([
                'password' => Hash::make($validated['password']),
            ]);
        }

        return redirect()
            ->route('admin.farm-users.index')
            ->with('success', 'Usuario de finca actualizado correctamente.');
    }

    public function destroy(FarmUser $farmUser): RedirectResponse
    {
        $farmUser->delete();

        return redirect()
            ->route('admin.farm-users.index')
            ->with('success', 'Asociación usuario-finca eliminada.');
    }
}
