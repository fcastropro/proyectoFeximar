<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDetail extends Model
{
    //
    protected $fillable = [
        'order_id',
        'farm_product_presentation_id',
        'boxes',
        'unit_price',
        'subtotal',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function presentation(): BelongsTo
    {
        return $this->belongsTo(FarmProductPresentation::class);
    }
}
