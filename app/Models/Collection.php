<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Collection extends Model
{
    protected $fillable = [
        'or_number', 'invoice_id', 'customer_id', 'collection_date',
        'amount', 'payment_method', 'reference_number',
        'bank_name', 'check_number', 'check_date', 'card_last4', 'approval_code',
        'notes', 'collected_by', 'deposit_id',
    ];

    protected $casts = [
        'collection_date' => 'date',
        'check_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'invoice_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function deposit(): BelongsTo
    {
        return $this->belongsTo(Deposit::class);
    }

    public function isDeposited(): bool
    {
        return ! is_null($this->deposit_id);
    }
}
