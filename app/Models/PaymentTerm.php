<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTerm extends Model
{
    protected $fillable = [
        'term_code', 'term_name', 'due_days', 'discount_pct', 'discount_days',
        'applies_to', 'status',
    ];
}
