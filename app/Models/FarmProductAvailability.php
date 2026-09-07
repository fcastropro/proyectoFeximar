<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FarmProductAvailability extends Model
{
    protected $fillable = [
        'farm_product_presentation_id',
        'year',
        'week_number',
        'available_stems',
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

    public function presentation(): BelongsTo
    {
        return $this->belongsTo(FarmProductPresentation::class, 'farm_product_presentation_id');
    }

    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }
}
