<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesInvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id', 'so_item_id', 'item_id', 'qty_invoiced',
        'unit_price', 'discount_pct', 'tax_rate_pct', 'line_total',
    ];

    protected $casts = [
        'qty_invoiced' => 'decimal:2',
        'unit_price' => 'decimal:4',
        'discount_pct' => 'decimal:2',
        'tax_rate_pct' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'invoice_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function soItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class, 'so_item_id');
    }
}
