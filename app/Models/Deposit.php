<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deposit extends Model
{
    protected $fillable = [
        'deposit_number', 'bank_account_id', 'deposit_date', 'slip_number',
        'cash_amount', 'check_amount', 'other_amount', 'total_amount',
        'status', 'notes', 'cancel_reason',
        'created_by', 'posted_by', 'posted_at',
        'cancelled_by', 'cancelled_at',
    ];

    protected $casts = [
        'deposit_date' => 'date',
        'posted_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'cash_amount' => 'decimal:2',
        'check_amount' => 'decimal:2',
        'other_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class, 'deposit_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    public function isPostable(): bool
    {
        return $this->status === 'draft';
    }

    public function isCancellable(): bool
    {
        return $this->status === 'draft';
    }
}
