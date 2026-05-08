<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptItem extends Model
{
    protected $fillable = [
        'grn_id', 'po_item_id', 'item_id',
        'qty_received', 'qty_accepted', 'qty_rejected',
        'rejection_reason', 'unit_cost',
    ];

    protected $casts = [
        'qty_received' => 'decimal:2',
        'qty_accepted' => 'decimal:2',
        'qty_rejected' => 'decimal:2',
        'unit_cost' => 'decimal:4',
    ];

    public function grn(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class, 'grn_id');
    }

    public function poItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'po_item_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
