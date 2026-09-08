<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FarmOrderFinance extends Model
{
    public const CONDITIONS = ['cash', 'credit'];

    public const STATUSES = ['pending', 'partial', 'paid', 'overdue'];

    protected $fillable = [
        'order_id',
        'farm_id',
        'amount',
        'payment_condition',
        'credit_days',
        'due_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'credit_days' => 'integer',
            'due_date' => 'date',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FarmPayment::class);
    }

    public function paidAmount(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function balance(): float
    {
        return round((float) $this->amount - $this->paidAmount(), 2);
    }

    public function refreshStatus(): void
    {
        $paid = $this->paidAmount();
        $amount = (float) $this->amount;

        if ($paid <= 0) {
            $status = ($this->due_date && $this->due_date->isPast()) ? 'overdue' : 'pending';
        } elseif ($paid + 0.0001 >= $amount) {
            $status = 'paid';
        } else {
            $status = ($this->due_date && $this->due_date->isPast()) ? 'overdue' : 'partial';
        }

        $this->update(['status' => $status]);
    }
}
