<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FarmProductPresentation extends Model
{
    //
    protected $fillable = [
        'farm_product_id',
        'box_type_id',
        'stem_length_cm',
        'stems_per_bunch',
        'bunches_per_box',
        'stems_per_box',
        'available_boxes',
        'price_per_stem',
        'price_per_bunch',
        'price_per_box',
        'active',
    ];

    public function farmProduct(): BelongsTo
    {
        return $this->belongsTo(FarmProduct::class);
    }

    public function boxType(): BelongsTo
    {
        return $this->belongsTo(BoxType::class);
    }

    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }
}
