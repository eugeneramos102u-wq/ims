<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Salesperson extends Model
{
    protected $table = 'salespersons';

    protected $fillable = [
        'sp_code', 'full_name', 'user_id', 'phone', 'territory',
        'commission_rate_pct', 'status',
    ];

    protected $casts = [
        'commission_rate_pct' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
