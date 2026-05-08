<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Item extends Model
{
    protected $fillable = [
        'item_code', 'item_name', 'category_id', 'uom_id', 'description', 'barcode',
        'unit_cost', 'wholesale_price', 'retail_price', 'tax_rate_pct',
        'qty_on_hand', 'reorder_level', 'status',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:4',
        'wholesale_price' => 'decimal:4',
        'retail_price' => 'decimal:4',
        'tax_rate_pct' => 'decimal:2',
        'qty_on_hand' => 'decimal:2',
        'reorder_level' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class, 'uom_id');
    }

    public function priceFor(string $type): float
    {
        return (float) ($type === 'wholesale' ? $this->wholesale_price : $this->retail_price);
    }

    public function isLowStock(): bool
    {
        return $this->qty_on_hand <= $this->reorder_level && $this->qty_on_hand > 0;
    }

    public function isZeroStock(): bool
    {
        return $this->qty_on_hand <= 0;
    }
}
