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

    public const PAYMENT_CASH = 'cash';

    public const PAYMENT_CREDIT = 'credit';

    public const SHIPPING_AIR = 'air';

    public const SHIPPING_SEA = 'sea';

    protected $fillable = [
        'buyer_id',
        'status',
        'total',
        'notes',
        'payment_condition',
        'credit_days',
        'cargo_agency_id',
        'shipping_method',
        'destination_country_id',
        'destination_city',
        'destination_airport',
        'destination_port',
        'marking',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'credit_days' => 'integer',
        ];
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    public function cargoAgency(): BelongsTo
    {
        return $this->belongsTo(CargoAgency::class);
    }

    public function destinationCountry(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'destination_country_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function farmFulfillments(): HasMany
    {
        return $this->hasMany(OrderFarmFulfillment::class);
    }

    public function farmFinances(): HasMany
    {
        return $this->hasMany(FarmOrderFinance::class);
    }
}
