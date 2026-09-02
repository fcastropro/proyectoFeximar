<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    //
     protected $fillable = [
        'buyer_id',
        'status',
        'total',
        'notes',
    ];

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }
}
