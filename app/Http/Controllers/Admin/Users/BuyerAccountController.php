<?php

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Buyer;
use App\Models\BuyerUser;
use App\Models\User;
use App\Services\Admin\ActivityLogger;
use App\Services\Admin\UserAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class BuyerAccountController extends Controller
{
    public function __construct(
        private readonly UserAccessService $access,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function index(Request $request): Response
    {
        $query = BuyerUser::query()
            ->with([
                'buyer:id,company_name,credit_allowed,credit_days_default,active',
                'user:id,name,email,active,created_at',
            ])
            ->orderByDesc('id');

        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->whereHas('user', function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }
        if ($request->filled('buyer_id')) {
            $query->where('buyer_id', $request->integer('buyer_id'));
        }
        if ($request->filled('role')) {
            $query->where('role', $request->string('role')->toString());
        }
        if ($request->filled('active')) {
            $active = $request->boolean('active');
            $query->where('active', $active)->whereHas('user', fn ($u) => $u->where('active', $active));
        }

        $buyerUsers = $query->paginate(15)->withQueryString()->through(fn (BuyerUser $item) => [
            'id' => $item->id,
            'buyer_id' => $item->buyer_id,
            'buyer_name' => $item->buyer?->company_name,
            'user_id' => $item->user_id,
            'user_name' => $item->user?->name,
            'user_email' => $item->user?->email,
            'role' => $item->role,
            'active' => (bool) $item->active && (bool) $item->user?->active,
            'credit_allowed' => (bool) $item->buyer?->credit_allowed,
            'credit_days_default' => $item->buyer?->credit_days_default,
            'created_at' => $item->user?->created_at?->format('Y-m-d H:i'),
        ]);

        return Inertia::render('Admin/Users/Buyers/Index', [
            'buyerUsers' => $buyerUsers,
            'filters' => [
                'q' => $request->input('q'),
                'buyer_id' => $request->input('buyer_id'),
                'role' => $request->input('role'),
                'active' => $request->input('active'),
            ],
            'buyers' => Buyer::query()->orderBy('company_name')->get(['id', 'company_name']),
            'roles' => BuyerUser::ROLES,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Buyers/Create', [
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
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => ['required', 'string', Rule::in(BuyerUser::ROLES)],
            'active' => ['boolean'],
            'credit_allowed' => ['boolean'],
            'credit_days_default' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        DB::transaction(function () use ($validated, $request) {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'active' => $request->boolean('active', true),
            ]);

            $this->access->assertCanBeBuyerUser($user);

            $link = BuyerUser::query()->create([
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

            $this->activityLogger->log(
                'buyer_user_created',
                "Usuario comprador creado: {$user->email} → buyer #{$link->buyer_id} ({$link->role})",
                BuyerUser::class,
                $link->id,
            );
        });

        return redirect()
            ->route('admin.users.buyers.index')
            ->with('success', 'Usuario comprador creado correctamente.');
    }

    public function show(BuyerUser $buyerUser): Response
    {
        $buyerUser->load(['user', 'buyer:id,company_name,credit_allowed,credit_days_default']);
        $user = $buyerUser->user;
        $logs = ActivityLog::query()
            ->where(function ($q) use ($buyerUser, $user) {
                $q->where(function ($inner) use ($buyerUser) {
                    $inner->where('entity_type', BuyerUser::class)->where('entity_id', $buyerUser->id);
                })->orWhere(function ($inner) use ($user) {
                    if ($user) {
                        $inner->where('entity_type', User::class)->where('entity_id', $user->id);
                    }
                });
            })
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return Inertia::render('Admin/Users/Buyers/Show', [
            'buyerUser' => [
                'id' => $buyerUser->id,
                'name' => $user?->name,
                'email' => $user?->email,
                'buyer_name' => $buyerUser->buyer?->company_name,
                'role' => $buyerUser->role,
                'active' => (bool) $buyerUser->active && (bool) $user?->active,
                'credit_allowed' => (bool) $buyerUser->buyer?->credit_allowed,
                'credit_days_default' => $buyerUser->buyer?->credit_days_default,
                'created_at' => $user?->created_at?->format('Y-m-d H:i'),
                'updated_at' => $user?->updated_at?->format('Y-m-d H:i'),
                'profile' => [
                    'type' => 'buyer',
                    'label' => 'Usuario comprador',
                    'entity' => $buyerUser->buyer?->company_name,
                    'role' => $buyerUser->role,
                ],
            ],
            'logs' => $logs->map(fn (ActivityLog $log) => [
                'action' => $log->action,
                'description' => $log->description,
                'created_at' => $log->created_at?->format('Y-m-d H:i'),
            ]),
        ]);
    }

    public function edit(BuyerUser $buyerUser): Response
    {
        $buyerUser->load(['user:id,name,email,active', 'buyer:id,company_name,credit_allowed,credit_days_default']);

        return Inertia::render('Admin/Users/Buyers/Edit', [
            'buyerUser' => [
                'id' => $buyerUser->id,
                'buyer_id' => $buyerUser->buyer_id,
                'user_name' => $buyerUser->user?->name,
                'user_email' => $buyerUser->user?->email,
                'role' => $buyerUser->role,
                'active' => (bool) $buyerUser->active && (bool) $buyerUser->user?->active,
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($buyerUser->user_id)],
            'role' => ['required', 'string', Rule::in(BuyerUser::ROLES)],
            'active' => ['boolean'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'credit_allowed' => ['boolean'],
            'credit_days_default' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $user = $buyerUser->user;
        abort_unless($user, 404);
        $this->access->assertCanBeBuyerUser($user);

        $oldBuyer = $buyerUser->buyer_id;
        $oldRole = $buyerUser->role;
        $active = $request->boolean('active');

        $userPayload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'active' => $active,
        ];
        if (! empty($validated['password'])) {
            $userPayload['password'] = $validated['password'];
        }
        $user->update($userPayload);

        $buyerUser->update([
            'buyer_id' => $validated['buyer_id'],
            'role' => $validated['role'],
            'active' => $active,
        ]);

        Buyer::query()->whereKey($validated['buyer_id'])->update([
            'credit_allowed' => $request->boolean('credit_allowed'),
            'credit_days_default' => $request->boolean('credit_allowed')
                ? ($validated['credit_days_default'] ?? null)
                : null,
        ]);

        if ((int) $oldBuyer !== (int) $validated['buyer_id']) {
            $this->activityLogger->log(
                'buyer_user_reassigned',
                "Usuario {$user->email} reasignado de comprador #{$oldBuyer} a #{$validated['buyer_id']}",
                BuyerUser::class,
                $buyerUser->id,
            );
        }
        if ($oldRole !== $validated['role']) {
            $this->activityLogger->log(
                'buyer_user_role_changed',
                "Usuario {$user->email} cambió rol de {$oldRole} a {$validated['role']}",
                BuyerUser::class,
                $buyerUser->id,
            );
        }

        $this->activityLogger->log(
            'buyer_user_updated',
            "Usuario comprador actualizado: {$user->email}",
            BuyerUser::class,
            $buyerUser->id,
        );

        return redirect()
            ->route('admin.users.buyers.index')
            ->with('success', 'Usuario comprador actualizado correctamente.');
    }

    public function toggleActive(BuyerUser $buyerUser): RedirectResponse
    {
        $user = $buyerUser->user;
        abort_unless($user, 404);

        $next = ! ((bool) $buyerUser->active && (bool) $user->active);
        $buyerUser->update(['active' => $next]);
        $user->update(['active' => $next]);

        $this->activityLogger->log(
            $next ? 'user_activated' : 'user_deactivated',
            ($next ? 'Activó' : 'Desactivó')." usuario comprador {$user->email}",
            BuyerUser::class,
            $buyerUser->id,
        );

        return back()->with('success', 'Estado actualizado.');
    }

    public function resetPassword(Request $request, BuyerUser $buyerUser): RedirectResponse
    {
        $user = $buyerUser->user;
        abort_unless($user, 404);

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $this->access->resetPassword($user, $validated['password']);

        return back()->with('success', 'Contraseña restablecida correctamente.');
    }

    public function destroy(BuyerUser $buyerUser): RedirectResponse
    {
        $email = $buyerUser->user?->email;
        $id = $buyerUser->id;
        $buyerUser->delete();

        $this->activityLogger->log(
            'buyer_user_unlinked',
            "Se eliminó el vínculo de comprador del usuario {$email}",
            BuyerUser::class,
            $id,
        );

        return redirect()
            ->route('admin.users.buyers.index')
            ->with('success', 'Vínculo usuario-comprador eliminado. La cuenta se mantiene (preferir desactivar).');
    }
}
