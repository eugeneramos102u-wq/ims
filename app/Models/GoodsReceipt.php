<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceipt extends Model
{
    protected $fillable = [
        'grn_number', 'po_id', 'supplier_id', 'received_date',
        'delivery_note_no', 'carrier', 'remarks', 'status', 'cancel_reason',
        'received_by', 'confirmed_by', 'confirmed_at',
        'cancelled_by', 'cancelled_at',
    ];

    protected $casts = [
        'received_date' => 'date',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class, 'grn_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    public function isConfirmable(): bool
    {
        return $this->status === 'draft';
    }

    public function isCancellable(): bool
    {
        return $this->status === 'draft';
    }

    public function totalAccepted(): float
    {
        return (float) $this->items->sum('qty_accepted');
    }

    public function totalRejected(): float
    {
        return (float) $this->items->sum('qty_rejected');
    }
}
