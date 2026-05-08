<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrder extends Model
{
    protected $fillable = [
        'so_number', 'dr_number', 'customer_id', 'salesperson_id', 'order_date',
        'required_date', 'price_type', 'shipping_address', 'currency_code',
        'subtotal', 'discount_amount', 'tax_amount', 'shipping_cost', 'total_amount',
        'commission_rate_pct', 'commission_amount', 'status', 'notes',
        'created_by', 'confirmed_by', 'confirmed_at',
    ];

    protected $casts = [
        'order_date' => 'date',
        'required_date' => 'date',
        'confirmed_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'commission_rate_pct' => 'decimal:2',
        'commission_amount' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(Salesperson::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class, 'so_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class, 'so_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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

    public function isInvoiceable(): bool
    {
        return in_array($this->status, ['confirmed', 'partial'], true);
    }
}
