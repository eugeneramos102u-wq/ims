<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'po_id', 'item_id', 'qty_ordered', 'qty_received',
        'unit_cost', 'tax_rate_pct', 'line_total',
    ];

    protected $casts = [
        'qty_ordered' => 'decimal:2',
        'qty_received' => 'decimal:2',
        'unit_cost' => 'decimal:4',
        'tax_rate_pct' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function qtyOutstanding(): float
    {
        return max(0, (float) $this->qty_ordered - (float) $this->qty_received);
    }
}
