<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Variety extends Model
{
    protected $fillable = [
        'flower_type_id',
        'name',
        'color',
        'description',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function flowerType(): BelongsTo
    {
        return $this->belongsTo(FlowerType::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'variety_id');
    }
}
