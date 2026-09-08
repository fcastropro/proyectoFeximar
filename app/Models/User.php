<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
        ];
    }

    public function farmUsers(): HasMany
    {
        return $this->hasMany(FarmUser::class);
    }

    public function buyerUsers(): HasMany
    {
        return $this->hasMany(BuyerUser::class);
    }

    public function farms(): BelongsToMany
    {
        return $this->belongsToMany(Farm::class, 'farm_users')
            ->withPivot(['id', 'role', 'active'])
            ->withTimestamps();
    }

    public function buyers(): BelongsToMany
    {
        return $this->belongsToMany(Buyer::class, 'buyer_users')
            ->withPivot(['id', 'role', 'active'])
            ->withTimestamps();
    }

    public function activeFarms(): BelongsToMany
    {
        return $this->farms()->wherePivot('active', true);
    }

    public function activeBuyers(): BelongsToMany
    {
        return $this->buyers()->wherePivot('active', true)->where('buyers.active', true);
    }

    public function accountIsActive(): bool
    {
        return (bool) $this->active;
    }

    public function isBuyerUser(): bool
    {
        return $this->accountIsActive() && $this->activeBuyers()->exists();
    }

    public function isFarmUser(): bool
    {
        return $this->accountIsActive() && $this->activeFarms()->exists();
    }

    public function isAdminUser(): bool
    {
        return $this->accountIsActive()
            && ! $this->activeFarms()->exists()
            && ! $this->activeBuyers()->exists();
    }

    public function primaryFarm(): ?Farm
    {
        return $this->activeFarms()->orderBy('farms.id')->first();
    }

    public function primaryBuyer(): ?Buyer
    {
        return $this->activeBuyers()->orderBy('buyers.id')->first();
    }

    /**
     * Destino post-login / botón "Mi panel".
     * Prioridad MVP: buyer → farm → admin.
     */
    public function portalDashboardRoute(): string
    {
        if ($this->isBuyerUser()) {
            return 'buyer.dashboard';
        }

        if ($this->isFarmUser()) {
            return 'farm.dashboard';
        }

        return 'admin.dashboard';
    }

    public function portalDashboardUrl(): string
    {
        return route($this->portalDashboardRoute());
    }
}
