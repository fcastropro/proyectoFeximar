<?php

namespace App\Services\Admin;

use App\Models\BuyerUser;
use App\Models\FarmUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserAccessService
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function assertAccountActive(User $user): void
    {
        if (! $user->accountIsActive()) {
            throw ValidationException::withMessages([
                'email' => 'Esta cuenta de usuario está desactivada.',
            ]);
        }
    }

    public function assertCanBeAdmin(User $user): void
    {
        if ($user->farmUsers()->where('active', true)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'No puede ser administrador: tiene vínculos activos de finca.',
            ]);
        }

        if ($user->buyerUsers()->where('active', true)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'No puede ser administrador: tiene vínculos activos de comprador.',
            ]);
        }
    }

    public function assertCanBeFarmUser(?User $user = null): void
    {
        if (! $user) {
            return;
        }

        if ($user->buyerUsers()->where('active', true)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Este usuario ya está asociado a un comprador. Perfiles incompatibles.',
            ]);
        }
    }

    public function assertCanBeBuyerUser(?User $user = null): void
    {
        if (! $user) {
            return;
        }

        if ($user->farmUsers()->where('active', true)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Este usuario ya está asociado a una finca. Perfiles incompatibles.',
            ]);
        }
    }

    public function activeAdminCount(): int
    {
        return User::query()
            ->where('active', true)
            ->whereDoesntHave('farmUsers', fn ($q) => $q->where('active', true))
            ->whereDoesntHave('buyerUsers', fn ($q) => $q->where('active', true))
            ->count();
    }

    public function assertNotLastActiveAdmin(User $user): void
    {
        if (! $user->isAdminUser()) {
            return;
        }

        if ($this->activeAdminCount() <= 1) {
            throw ValidationException::withMessages([
                'active' => 'No se puede desactivar o eliminar el último administrador activo.',
            ]);
        }
    }

    public function deactivateAdminPivots(User $user): void
    {
        $user->farmUsers()->update(['active' => false]);
        $user->buyerUsers()->update(['active' => false]);
    }

    /**
     * @param  array{name:string,email:string,password:string,active?:bool}  $data
     */
    public function createAdmin(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'active' => $data['active'] ?? true,
            ]);

            $this->activityLogger->log(
                'user_created',
                "Administrador creado: {$user->email}",
                User::class,
                $user->id,
            );

            return $user;
        });
    }

    /**
     * @param  array{name:string,email:string,password:?string,active:bool}  $data
     */
    public function updateAdmin(User $user, array $data): User
    {
        $this->assertCanBeAdmin($user);

        $becomingInactive = $user->active && ! $data['active'];
        if ($becomingInactive) {
            $this->assertNotLastActiveAdmin($user);
        }

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'active' => $data['active'],
        ];

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $user->update($payload);
        $this->deactivateAdminPivots($user);

        $this->activityLogger->log(
            'user_updated',
            "Administrador actualizado: {$user->email}",
            User::class,
            $user->id,
        );

        return $user->fresh();
    }

    public function setActive(User $user, bool $active): User
    {
        if ($user->isAdminUser() && ! $active) {
            $this->assertNotLastActiveAdmin($user);
        }

        $user->update(['active' => $active]);

        $this->activityLogger->log(
            $active ? 'user_activated' : 'user_deactivated',
            ($active ? 'Activó' : 'Desactivó')." usuario {$user->email}",
            User::class,
            $user->id,
        );

        return $user->fresh();
    }

    public function resetPassword(User $user, string $password): void
    {
        $user->update(['password' => $password]);

        $this->activityLogger->log(
            'password_reset',
            "Administrador restableció contraseña de usuario {$user->email}",
            User::class,
            $user->id,
        );
    }

    /**
     * @return array{type:string,label:string,entity:?string,role:?string}
     */
    public function accessProfile(User $user): array
    {
        if ($user->isBuyerUser()) {
            $link = $user->buyerUsers()->where('active', true)->with('buyer:id,company_name')->first();

            return [
                'type' => 'buyer',
                'label' => 'Usuario comprador',
                'entity' => $link?->buyer?->company_name,
                'role' => $link?->role,
            ];
        }

        if ($user->isFarmUser()) {
            $link = $user->farmUsers()->where('active', true)->with('farm:id,name')->first();

            return [
                'type' => 'farm',
                'label' => 'Usuario de finca',
                'entity' => $link?->farm?->name,
                'role' => $link?->role,
            ];
        }

        if ($user->accountIsActive()) {
            return [
                'type' => 'admin',
                'label' => 'Administrador FEXIMAR',
                'entity' => null,
                'role' => 'admin',
            ];
        }

        return [
            'type' => 'none',
            'label' => 'Sin perfil operativo válido',
            'entity' => null,
            'role' => null,
        ];
    }

    public function usersWithoutValidProfileCount(): int
    {
        return User::query()
            ->with(['farmUsers', 'buyerUsers'])
            ->get()
            ->filter(fn (User $user) => ! $user->isAdminUser() && ! $user->isFarmUser() && ! $user->isBuyerUser())
            ->count();
    }

    public function inactiveUsersCount(): int
    {
        return User::query()->where('active', false)->count();
    }

    public function activeFarmUsersCount(): int
    {
        return FarmUser::query()
            ->where('active', true)
            ->whereHas('user', fn ($q) => $q->where('active', true))
            ->count();
    }

    public function activeBuyerUsersCount(): int
    {
        return BuyerUser::query()
            ->where('active', true)
            ->whereHas('user', fn ($q) => $q->where('active', true))
            ->count();
    }

    /**
     * @return array{active_admins:int,active_farm_users:int,active_buyer_users:int,inactive_users:int,invalid_profile:int}
     */
    public function usersDashboardKpis(): array
    {
        return [
            'active_admins' => $this->activeAdminCount(),
            'active_farm_users' => $this->activeFarmUsersCount(),
            'active_buyer_users' => $this->activeBuyerUsersCount(),
            'inactive_users' => $this->inactiveUsersCount(),
            'invalid_profile' => $this->usersWithoutValidProfileCount(),
        ];
    }
}
