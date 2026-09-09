<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    protected $fillable = [
        'name',
        'variety_id',
        'category',
        'variety',
        'color',
        'description',
        'image',
        'image_path',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function catalogVariety(): BelongsTo
    {
        return $this->belongsTo(Variety::class, 'variety_id');
    }

    public function farmProducts(): HasMany
    {
        return $this->hasMany(FarmProduct::class);
    }

    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return Storage::disk('public')->url($this->image_path);
    }
}
