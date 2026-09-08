<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BuyerCart extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_CONVERTED = 'converted';

    protected $fillable = [
        'buyer_id',
        'user_id',
        'status',
    ];

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BuyerCartItem::class);
    }
}
