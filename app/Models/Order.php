<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const STATUSES = [
        'pending',
        'confirmed',
        'processing',
        'shipped',
        'cancelled',
    ];

    protected $fillable = [
        'buyer_id',
        'status',
        'total',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
        ];
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }
}
