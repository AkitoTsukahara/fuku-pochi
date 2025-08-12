<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Children extends Model
{
    protected $table = 'children';
    
    protected $fillable = [
        'user_group_id',
        'name',
    ];

    public function userGroup(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class);
    }

    public function stockItems(): HasMany
    {
        return $this->hasMany(StockItem::class, 'child_id');
    }
}
