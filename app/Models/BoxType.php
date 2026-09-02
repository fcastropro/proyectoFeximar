<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoxType extends Model
{
    //
    protected $fillable = [
        'code',
        'name',
        'active',
    ];

    public function presentations(): HasMany
    {
        return $this->hasMany(FarmProductPresentation::class);
    }
}
