<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustmentItem extends Model
{
    protected $fillable = [
        'adj_id', 'item_id', 'qty_before', 'qty_adjusted', 'qty_after',
        'unit_cost', 'notes',
    ];

    protected $casts = [
        'qty_before' => 'decimal:2',
        'qty_adjusted' => 'decimal:2',
        'qty_after' => 'decimal:2',
        'unit_cost' => 'decimal:4',
    ];

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class, 'adj_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
