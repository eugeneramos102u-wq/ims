<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model
{
    protected $fillable = [
        'bank_name', 'account_number', 'account_name', 'branch',
        'is_default', 'status', 'notes',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    public function maskedNumber(): string
    {
        $n = $this->account_number ?? '';

        return strlen($n) > 4 ? '••••' . substr($n, -4) : $n;
    }

    public function label(): string
    {
        return $this->bank_name . ' ' . $this->maskedNumber();
    }
}
