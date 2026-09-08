<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuyerCartItem extends Model
{
    protected $fillable = [
        'buyer_cart_id',
        'farm_product_availability_id',
        'bunches',
        'stems_per_bunch_snapshot',
        'total_stems',
        'price_per_stem_snapshot',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'bunches' => 'integer',
            'stems_per_bunch_snapshot' => 'integer',
            'total_stems' => 'integer',
            'price_per_stem_snapshot' => 'decimal:4',
            'subtotal' => 'decimal:2',
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(BuyerCart::class, 'buyer_cart_id');
    }

    public function availability(): BelongsTo
    {
        return $this->belongsTo(FarmProductAvailability::class, 'farm_product_availability_id');
    }
}
