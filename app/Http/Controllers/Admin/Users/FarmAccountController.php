<?php

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Farm;
use App\Models\FarmUser;
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

class FarmAccountController extends Controller
{
    public function __construct(
        private readonly UserAccessService $access,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function index(Request $request): Response
    {
        $query = FarmUser::query()
            ->with(['farm:id,name', 'user:id,name,email,active,created_at'])
            ->orderByDesc('id');

        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->whereHas('user', function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }
        if ($request->filled('farm_id')) {
            $query->where('farm_id', $request->integer('farm_id'));
        }
        if ($request->filled('role')) {
            $query->where('role', $request->string('role')->toString());
        }
        if ($request->filled('active')) {
            $active = $request->boolean('active');
            $query->where('active', $active)->whereHas('user', fn ($u) => $u->where('active', $active));
        }

        $farmUsers = $query->paginate(15)->withQueryString()->through(fn (FarmUser $item) => [
            'id' => $item->id,
            'farm_id' => $item->farm_id,
            'farm_name' => $item->farm?->name,
            'user_id' => $item->user_id,
            'user_name' => $item->user?->name,
            'user_email' => $item->user?->email,
            'role' => $item->role,
            'active' => (bool) $item->active && (bool) $item->user?->active,
            'pivot_active' => (bool) $item->active,
            'user_active' => (bool) $item->user?->active,
            'created_at' => $item->user?->created_at?->format('Y-m-d H:i'),
        ]);

        return Inertia::render('Admin/Users/Farms/Index', [
            'farmUsers' => $farmUsers,
            'filters' => [
                'q' => $request->input('q'),
                'farm_id' => $request->input('farm_id'),
                'role' => $request->input('role'),
                'active' => $request->input('active'),
            ],
            'farms' => Farm::query()->orderBy('name')->get(['id', 'name']),
            'roles' => FarmUser::ROLES,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Farms/Create', [
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
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => ['required', 'string', Rule::in(FarmUser::ROLES)],
            'active' => ['boolean'],
        ]);

        DB::transaction(function () use ($validated, $request) {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'active' => $request->boolean('active', true),
            ]);

            $this->access->assertCanBeFarmUser($user);

            $link = FarmUser::query()->create([
                'farm_id' => $validated['farm_id'],
                'user_id' => $user->id,
                'role' => $validated['role'],
                'active' => $request->boolean('active', true),
            ]);

            $this->activityLogger->log(
                'farm_user_created',
                "Usuario de finca creado: {$user->email} → finca #{$link->farm_id} ({$link->role})",
                FarmUser::class,
                $link->id,
            );
        });

        return redirect()
            ->route('admin.users.farms.index')
            ->with('success', 'Usuario de finca creado correctamente.');
    }

    public function show(FarmUser $farmUser): Response
    {
        $farmUser->load(['user', 'farm:id,name']);
        $user = $farmUser->user;
        $logs = ActivityLog::query()
            ->where(function ($q) use ($farmUser, $user) {
                $q->where(function ($inner) use ($farmUser) {
                    $inner->where('entity_type', FarmUser::class)->where('entity_id', $farmUser->id);
                })->orWhere(function ($inner) use ($user) {
                    if ($user) {
                        $inner->where('entity_type', User::class)->where('entity_id', $user->id);
                    }
                });
            })
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return Inertia::render('Admin/Users/Farms/Show', [
            'farmUser' => [
                'id' => $farmUser->id,
                'name' => $user?->name,
                'email' => $user?->email,
                'farm_name' => $farmUser->farm?->name,
                'role' => $farmUser->role,
                'active' => (bool) $farmUser->active && (bool) $user?->active,
                'created_at' => $user?->created_at?->format('Y-m-d H:i'),
                'updated_at' => $user?->updated_at?->format('Y-m-d H:i'),
                'profile' => [
                    'type' => 'farm',
                    'label' => 'Usuario de finca',
                    'entity' => $farmUser->farm?->name,
                    'role' => $farmUser->role,
                ],
            ],
            'logs' => $logs->map(fn (ActivityLog $log) => [
                'action' => $log->action,
                'description' => $log->description,
                'created_at' => $log->created_at?->format('Y-m-d H:i'),
            ]),
        ]);
    }

    public function edit(FarmUser $farmUser): Response
    {
        $farmUser->load(['user:id,name,email,active', 'farm:id,name']);

        return Inertia::render('Admin/Users/Farms/Edit', [
            'farmUser' => [
                'id' => $farmUser->id,
                'farm_id' => $farmUser->farm_id,
                'user_name' => $farmUser->user?->name,
                'user_email' => $farmUser->user?->email,
                'role' => $farmUser->role,
                'active' => (bool) $farmUser->active && (bool) $farmUser->user?->active,
            ],
            'farms' => Farm::query()->where('active', true)->orderBy('name')->get(['id', 'name']),
            'roles' => FarmUser::ROLES,
        ]);
    }

    public function update(Request $request, FarmUser $farmUser): RedirectResponse
    {
        $validated = $request->validate([
            'farm_id' => ['required', 'integer', 'exists:farms,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($farmUser->user_id)],
            'role' => ['required', 'string', Rule::in(FarmUser::ROLES)],
            'active' => ['boolean'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        $user = $farmUser->user;
        abort_unless($user, 404);

        $this->access->assertCanBeFarmUser($user);

        $oldFarm = $farmUser->farm_id;
        $oldRole = $farmUser->role;
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

        $farmUser->update([
            'farm_id' => $validated['farm_id'],
            'role' => $validated['role'],
            'active' => $active,
        ]);

        if ((int) $oldFarm !== (int) $validated['farm_id']) {
            $this->activityLogger->log(
                'farm_user_reassigned',
                "Usuario {$user->email} reasignado de finca #{$oldFarm} a #{$validated['farm_id']}",
                FarmUser::class,
                $farmUser->id,
            );
        }
        if ($oldRole !== $validated['role']) {
            $this->activityLogger->log(
                'farm_user_role_changed',
                "Usuario {$user->email} cambió rol de {$oldRole} a {$validated['role']}",
                FarmUser::class,
                $farmUser->id,
            );
        }

        $this->activityLogger->log(
            'farm_user_updated',
            "Usuario de finca actualizado: {$user->email}",
            FarmUser::class,
            $farmUser->id,
        );

        return redirect()
            ->route('admin.users.farms.index')
            ->with('success', 'Usuario de finca actualizado correctamente.');
    }

    public function toggleActive(FarmUser $farmUser): RedirectResponse
    {
        $user = $farmUser->user;
        abort_unless($user, 404);

        $next = ! ((bool) $farmUser->active && (bool) $user->active);
        $farmUser->update(['active' => $next]);
        $user->update(['active' => $next]);

        $this->activityLogger->log(
            $next ? 'user_activated' : 'user_deactivated',
            ($next ? 'Activó' : 'Desactivó')." usuario de finca {$user->email}",
            FarmUser::class,
            $farmUser->id,
        );

        return back()->with('success', 'Estado actualizado.');
    }

    public function resetPassword(Request $request, FarmUser $farmUser): RedirectResponse
    {
        $user = $farmUser->user;
        abort_unless($user, 404);

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $this->access->resetPassword($user, $validated['password']);

        return back()->with('success', 'Contraseña restablecida correctamente.');
    }

    public function destroy(FarmUser $farmUser): RedirectResponse
    {
        $email = $farmUser->user?->email;
        $id = $farmUser->id;
        $farmUser->delete();

        $this->activityLogger->log(
            'farm_user_unlinked',
            "Se eliminó el vínculo de finca del usuario {$email}",
            FarmUser::class,
            $id,
        );

        return redirect()
            ->route('admin.users.farms.index')
            ->with('success', 'Vínculo usuario-finca eliminado. La cuenta de usuario se mantiene (preferir desactivar).');
    }
}
