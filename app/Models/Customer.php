<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'customer_code', 'company_name', 'contact_person', 'email', 'phone',
        'billing_address', 'shipping_address', 'tax_id', 'credit_limit',
        'payment_term_id', 'notes', 'status',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
    ];

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class);
    }

    public function outstandingBalance(): float
    {
        return (float) $this->invoices()
            ->whereIn('status', ['issued', 'partial', 'overdue'])
            ->selectRaw('COALESCE(SUM(total_amount - amount_paid), 0) as bal')
            ->value('bal');
    }
}
