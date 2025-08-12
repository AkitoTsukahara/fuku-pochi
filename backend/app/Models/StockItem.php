<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockItem extends Model
{
    protected $fillable = [
        'child_id',
        'clothing_category_id',
        'current_count',
    ];

    protected $casts = [
        'current_count' => 'integer',
    ];

    public function child(): BelongsTo
    {
        return $this->belongsTo(Children::class);
    }

    public function clothingCategory(): BelongsTo
    {
        return $this->belongsTo(ClothingCategory::class);
    }
}
