<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FarmProduct extends Model
{
    //
    protected $fillable = [
        'farm_id',
        'product_id',
        'active',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function presentations(): HasMany
    {
        return $this->hasMany(FarmProductPresentation::class);
    }
}
