<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderFarmFulfillment extends Model
{
    public const STATUSES = [
        'pending',
        'accepted',
        'rejected',
        'preparing',
        'ready',
        'dispatched',
        'completed',
        'cancelled',
    ];

    public const TRANSITIONS = [
        'pending' => ['accepted', 'rejected', 'cancelled'],
        'accepted' => ['preparing', 'cancelled'],
        'preparing' => ['ready', 'cancelled'],
        'ready' => ['dispatched', 'cancelled'],
        'dispatched' => ['completed'],
        'rejected' => [],
        'completed' => [],
        'cancelled' => [],
    ];

    protected $fillable = [
        'order_id',
        'farm_id',
        'status',
        'received_at',
        'accepted_at',
        'rejected_at',
        'rejection_reason',
        'prepared_at',
        'ready_at',
        'dispatched_at',
        'completed_at',
        'stems_reserved',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
            'prepared_at' => 'datetime',
            'ready_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'completed_at' => 'datetime',
            'stems_reserved' => 'boolean',
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

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::TRANSITIONS[$this->status] ?? [], true);
    }
}
