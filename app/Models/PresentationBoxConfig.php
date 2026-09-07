<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresentationBoxConfig extends Model
{
    protected $fillable = [
        'farm_product_presentation_id',
        'box_type_id',
        'stems_per_box',
        'bunches_per_box',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'stems_per_box' => 'integer',
            'bunches_per_box' => 'integer',
        ];
    }

    public function presentation(): BelongsTo
    {
        return $this->belongsTo(FarmProductPresentation::class, 'farm_product_presentation_id');
    }

    public function boxType(): BelongsTo
    {
        return $this->belongsTo(BoxType::class);
    }
}
