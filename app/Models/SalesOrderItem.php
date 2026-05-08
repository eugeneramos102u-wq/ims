<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderItem extends Model
{
    protected $fillable = [
        'so_id', 'item_id', 'qty_ordered', 'qty_invoiced',
        'unit_price', 'discount_pct', 'tax_rate_pct', 'line_total',
    ];

    protected $casts = [
        'qty_ordered' => 'decimal:2',
        'qty_invoiced' => 'decimal:2',
        'unit_price' => 'decimal:4',
        'discount_pct' => 'decimal:2',
        'tax_rate_pct' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'so_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function qtyRemaining(): float
    {
        return max(0, (float) $this->qty_ordered - (float) $this->qty_invoiced);
    }
}
