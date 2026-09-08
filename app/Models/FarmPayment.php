<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FarmPayment extends Model
{
    protected $fillable = [
        'farm_order_finance_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    public function finance(): BelongsTo
    {
        return $this->belongsTo(FarmOrderFinance::class, 'farm_order_finance_id');
    }
}
