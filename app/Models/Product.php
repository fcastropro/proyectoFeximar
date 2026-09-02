<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    //
     protected $fillable = [
        'name',
        'category',
        'variety',
        'color',
        'description',
        'image',
        'active',
    ];

    public function farmProducts(): HasMany
    {
        return $this->hasMany(FarmProduct::class);
    }
}
