<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesInvoice extends Model
{
    protected $fillable = [
        'invoice_number', 'customer_id', 'so_id', 'invoice_date', 'due_date',
        'subtotal', 'discount_amount', 'tax_amount', 'total_amount', 'amount_paid',
        'status', 'notes', 'created_by', 'issued_by', 'issued_at',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'issued_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'so_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesInvoiceItem::class, 'invoice_id');
    }

    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class, 'invoice_id');
    }

    public function balanceDue(): Attribute
    {
        return Attribute::get(fn () => round((float) $this->total_amount - (float) $this->amount_paid, 2));
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast()
            && ! in_array($this->status, ['paid', 'void'], true);
    }
}
