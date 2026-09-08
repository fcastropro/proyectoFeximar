<?php

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\Admin\UserAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class AdminAccountController extends Controller
{
    public function __construct(
        private readonly UserAccessService $access,
    ) {}

    public function index(Request $request): Response
    {
        $query = $this->adminQuery();

        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        if ($request->filled('active')) {
            $query->where('active', $request->boolean('active'));
        }

        $admins = $query->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'active' => (bool) $user->active,
                'created_at' => $user->created_at?->format('Y-m-d H:i'),
                'updated_at' => $user->updated_at?->format('Y-m-d H:i'),
            ]);

        return Inertia::render('Admin/Users/Admins/Index', [
            'admins' => $admins,
            'filters' => [
                'q' => $request->input('q'),
                'active' => $request->input('active'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Admins/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'active' => ['boolean'],
        ]);

        $this->access->createAdmin([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'active' => $request->boolean('active', true),
        ]);

        return redirect()
            ->route('admin.users.admins.index')
            ->with('success', 'Administrador creado correctamente.');
    }

    public function show(User $user): Response
    {
        $this->assertIsAdminAccount($user);

        $profile = $this->access->accessProfile($user);
        $logs = ActivityLog::query()
            ->where('entity_type', User::class)
            ->where('entity_id', $user->id)
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'action', 'description', 'created_at']);

        return Inertia::render('Admin/Users/Admins/Show', [
            'admin' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'active' => (bool) $user->active,
                'created_at' => $user->created_at?->format('Y-m-d H:i'),
                'updated_at' => $user->updated_at?->format('Y-m-d H:i'),
                'profile' => $profile,
            ],
            'logs' => $logs->map(fn (ActivityLog $log) => [
                'action' => $log->action,
                'description' => $log->description,
                'created_at' => $log->created_at?->format('Y-m-d H:i'),
            ]),
        ]);
    }

    public function edit(User $user): Response
    {
        $this->assertIsAdminAccount($user);

        return Inertia::render('Admin/Users/Admins/Edit', [
            'admin' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'active' => (bool) $user->active,
            ],
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->assertIsAdminAccount($user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'active' => ['boolean'],
        ]);

        $this->access->updateAdmin($user, [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'] ?? null,
            'active' => $request->boolean('active'),
        ]);

        return redirect()
            ->route('admin.users.admins.index')
            ->with('success', 'Administrador actualizado correctamente.');
    }

    public function toggleActive(User $user): RedirectResponse
    {
        $this->assertIsAdminAccount($user);
        $this->access->setActive($user, ! $user->active);

        return back()->with('success', 'Estado del administrador actualizado.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $this->assertIsAdminAccount($user);

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $this->access->resetPassword($user, $validated['password']);

        return back()->with('success', 'Contraseña restablecida correctamente.');
    }

    private function adminQuery()
    {
        return User::query()
            ->whereDoesntHave('farmUsers', fn ($q) => $q->where('active', true))
            ->whereDoesntHave('buyerUsers', fn ($q) => $q->where('active', true));
    }

    private function assertIsAdminAccount(User $user): void
    {
        $hasActiveFarm = $user->farmUsers()->where('active', true)->exists();
        $hasActiveBuyer = $user->buyerUsers()->where('active', true)->exists();

        abort_if($hasActiveFarm || $hasActiveBuyer, 404);
    }
}
