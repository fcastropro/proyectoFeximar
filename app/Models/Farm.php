<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Farm extends Model
{
    protected $fillable = [
        'name',
        'commercial_name',
        'ruc',
        'email',
        'phone',
        'country_id',
        'province_id',
        'city_id',
        'address',
        'description',
        'logo',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function farmProducts(): HasMany
    {
        return $this->hasMany(FarmProduct::class);
    }

    public function farmUsers(): HasMany
    {
        return $this->hasMany(FarmUser::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'farm_users')
            ->withPivot(['id', 'role', 'active'])
            ->withTimestamps();
    }

    public function fulfillments(): HasMany
    {
        return $this->hasMany(OrderFarmFulfillment::class);
    }

    public function orderFinances(): HasMany
    {
        return $this->hasMany(FarmOrderFinance::class);
    }
}
