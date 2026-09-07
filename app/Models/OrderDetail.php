<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDetail extends Model
{
    protected $fillable = [
        'order_id',
        'farm_product_availability_id',
        'box_type_id',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:4',
            'subtotal' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function availability(): BelongsTo
    {
        return $this->belongsTo(FarmProductAvailability::class, 'farm_product_availability_id');
    }

    public function boxType(): BelongsTo
    {
        return $this->belongsTo(BoxType::class);
    }
}
