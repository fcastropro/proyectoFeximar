<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
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
}
