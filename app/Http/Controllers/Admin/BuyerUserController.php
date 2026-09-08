<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\BuyerUser;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BuyerUserController extends Controller
{
    public function index(): Response
    {
        $buyerUsers = BuyerUser::query()
            ->with(['buyer:id,company_name,credit_allowed,credit_days_default', 'user:id,name,email'])
            ->orderByDesc('id')
            ->get()
            ->map(fn (BuyerUser $item) => [
                'id' => $item->id,
                'buyer_id' => $item->buyer_id,
                'buyer_name' => $item->buyer?->company_name,
                'user_name' => $item->user?->name,
                'user_email' => $item->user?->email,
                'role' => $item->role,
                'active' => (bool) $item->active,
                'credit_allowed' => (bool) $item->buyer?->credit_allowed,
                'credit_days_default' => $item->buyer?->credit_days_default,
            ]);

        return Inertia::render('Admin/BuyerUsers/Index', [
            'buyerUsers' => $buyerUsers,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/BuyerUsers/Create', [
            'buyers' => Buyer::query()->where('active', true)->orderBy('company_name')->get([
                'id', 'company_name', 'credit_allowed', 'credit_days_default',
            ]),
            'roles' => BuyerUser::ROLES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'buyer_id' => ['required', 'integer', 'exists:buyers,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', Rule::in(BuyerUser::ROLES)],
            'active' => ['boolean'],
            'credit_allowed' => ['boolean'],
            'credit_days_default' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // MVP: no mezclar perfiles farm/admin/buyer.
        if ($user->isFarmUser()) {
            throw ValidationException::withMessages([
                'email' => 'Este usuario ya está asociado a una finca.',
            ]);
        }

        BuyerUser::create([
            'buyer_id' => $validated['buyer_id'],
            'user_id' => $user->id,
            'role' => $validated['role'],
            'active' => $request->boolean('active', true),
        ]);

        Buyer::query()->whereKey($validated['buyer_id'])->update([
            'credit_allowed' => $request->boolean('credit_allowed'),
            'credit_days_default' => $request->boolean('credit_allowed')
                ? ($validated['credit_days_default'] ?? null)
                : null,
        ]);

        return redirect()
            ->route('admin.buyer-users.index')
            ->with('success', 'Usuario comprador creado correctamente.');
    }

    public function edit(BuyerUser $buyerUser): Response
    {
        $buyerUser->load(['user:id,name,email', 'buyer:id,company_name,credit_allowed,credit_days_default']);

        return Inertia::render('Admin/BuyerUsers/Edit', [
            'buyerUser' => [
                'id' => $buyerUser->id,
                'buyer_id' => $buyerUser->buyer_id,
                'user_name' => $buyerUser->user?->name,
                'user_email' => $buyerUser->user?->email,
                'role' => $buyerUser->role,
                'active' => (bool) $buyerUser->active,
                'credit_allowed' => (bool) $buyerUser->buyer?->credit_allowed,
                'credit_days_default' => $buyerUser->buyer?->credit_days_default,
            ],
            'buyers' => Buyer::query()->where('active', true)->orderBy('company_name')->get([
                'id', 'company_name', 'credit_allowed', 'credit_days_default',
            ]),
            'roles' => BuyerUser::ROLES,
        ]);
    }

    public function update(Request $request, BuyerUser $buyerUser): RedirectResponse
    {
        $validated = $request->validate([
            'buyer_id' => ['required', 'integer', 'exists:buyers,id'],
            'role' => ['required', 'string', Rule::in(BuyerUser::ROLES)],
            'active' => ['boolean'],
            'password' => ['nullable', 'string', 'min:8'],
            'credit_allowed' => ['boolean'],
            'credit_days_default' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $buyerUser->update([
            'buyer_id' => $validated['buyer_id'],
            'role' => $validated['role'],
            'active' => $request->boolean('active'),
        ]);

        if (! empty($validated['password'])) {
            $buyerUser->user?->update([
                'password' => Hash::make($validated['password']),
            ]);
        }

        Buyer::query()->whereKey($validated['buyer_id'])->update([
            'credit_allowed' => $request->boolean('credit_allowed'),
            'credit_days_default' => $request->boolean('credit_allowed')
                ? ($validated['credit_days_default'] ?? null)
                : null,
        ]);

        return redirect()
            ->route('admin.buyer-users.index')
            ->with('success', 'Usuario comprador actualizado correctamente.');
    }

    public function destroy(BuyerUser $buyerUser): RedirectResponse
    {
        $buyerUser->delete();

        return redirect()
            ->route('admin.buyer-users.index')
            ->with('success', 'Asociación usuario-comprador eliminada.');
    }
}
