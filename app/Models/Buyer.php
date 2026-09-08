<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Buyer extends Model
{
    protected $fillable = [
        'user_id',
        'company_name',
        'contact_name',
        'email',
        'phone',
        'country_id',
        'country',
        'city',
        'address',
        'active',
        'credit_allowed',
        'credit_days_default',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'credit_allowed' => 'boolean',
            'credit_days_default' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function buyerUsers(): HasMany
    {
        return $this->hasMany(BuyerUser::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'buyer_users')
            ->withPivot(['id', 'role', 'active'])
            ->withTimestamps();
    }

    public function carts(): HasMany
    {
        return $this->hasMany(BuyerCart::class);
    }
}
