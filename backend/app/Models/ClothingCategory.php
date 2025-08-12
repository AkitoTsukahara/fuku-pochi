<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClothingCategory extends Model
{
    protected $fillable = [
        'name',
        'icon_path',
        'sort_order',
    ];

    public function stockItems(): HasMany
    {
        return $this->hasMany(StockItem::class);
    }
}
