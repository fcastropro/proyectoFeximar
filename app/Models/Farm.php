<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Farm extends Model
{
     protected $fillable = [
        'name',
        'commercial_name',
        'ruc',
        'email',
        'phone',
        'city',
        'province',
        'address',
        'description',
        'logo',
        'active',
    ];

    public function farmProducts(): HasMany
    {
        return $this->hasMany(FarmProduct::class);
    }
}
