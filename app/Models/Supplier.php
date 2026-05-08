<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'supplier_code', 'company_name', 'contact_person', 'email', 'phone',
        'address', 'tax_id', 'bank_name', 'bank_account', 'payment_term_id',
        'notes', 'status',
    ];

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function openBalance(): float
    {
        return (float) $this->purchaseOrders()
            ->whereIn('status', ['issued', 'partial'])
            ->selectRaw('COALESCE(SUM(total_amount - amount_received_value), 0) as bal')
            ->value('bal');
    }
}
