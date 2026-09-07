<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FarmProductPresentation extends Model
{
    protected $fillable = [
        'farm_product_id',
        'stem_length_cm',
        'stems_per_bunch',
        'price_per_stem',
        'price_per_bunch',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'price_per_stem' => 'decimal:4',
            'price_per_bunch' => 'decimal:2',
        ];
    }

    public function farmProduct(): BelongsTo
    {
        return $this->belongsTo(FarmProduct::class);
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(FarmProductAvailability::class);
    }

    public function boxConfigs(): HasMany
    {
        return $this->hasMany(PresentationBoxConfig::class);
    }
}
