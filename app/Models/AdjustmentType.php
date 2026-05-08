<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdjustmentType extends Model
{
    protected $fillable = ['code', 'type_name', 'direction', 'description', 'status'];

    public function adjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class);
    }

    public function isInbound(): bool
    {
        return in_array($this->direction, ['IN', 'BOTH'], true);
    }

    public function isOutbound(): bool
    {
        return $this->direction === 'OUT';
    }
}
