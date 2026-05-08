<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'po_number', 'supplier_id', 'payment_term_id', 'order_date', 'expected_date',
        'delivery_address', 'currency_code',
        'subtotal', 'tax_amount', 'total_amount', 'amount_received_value',
        'status', 'notes', 'cancel_reason',
        'created_by', 'issued_by', 'issued_at',
        'cancelled_by', 'cancelled_at',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_date' => 'date',
        'issued_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_received_value' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'po_id');
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class, 'po_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    public function isIssuable(): bool
    {
        return $this->status === 'draft';
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, ['draft', 'issued'], true);
    }

    public function isReceivable(): bool
    {
        return in_array($this->status, ['issued', 'partial'], true);
    }
}
