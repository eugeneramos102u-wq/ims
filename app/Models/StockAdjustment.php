<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockAdjustment extends Model
{
    protected $fillable = [
        'adj_number', 'adjustment_type_id', 'adjustment_date', 'notes',
        'status', 'void_reason',
        'created_by', 'posted_by', 'posted_at', 'voided_by', 'voided_at',
    ];

    protected $casts = [
        'adjustment_date' => 'date',
        'posted_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(AdjustmentType::class, 'adjustment_type_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class, 'adj_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    public function isPostable(): bool
    {
        return $this->status === 'draft';
    }

    public function isVoidable(): bool
    {
        return $this->status === 'posted';
    }
}
