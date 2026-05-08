<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Uom extends Model
{
    protected $table = 'units_of_measurement';

    protected $fillable = ['uom_code', 'uom_name', 'category', 'status'];

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'uom_id');
    }
}
