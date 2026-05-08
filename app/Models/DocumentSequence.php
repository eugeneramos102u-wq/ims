<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DocumentSequence extends Model
{
    protected $fillable = ['seq_key', 'prefix', 'next_number', 'padding'];

    /**
     * Atomically increment and return the next document number for a sequence key.
     * Uses a row lock so concurrent calls cannot produce the same number.
     */
    public static function next(string $key): string
    {
        return DB::transaction(function () use ($key) {
            $seq = self::where('seq_key', $key)->lockForUpdate()->first();

            if (! $seq) {
                throw new \RuntimeException("Document sequence not configured: {$key}");
            }

            $number = $seq->next_number;
            $seq->next_number = $number + 1;
            $seq->save();

            return $seq->prefix . str_pad((string) $number, $seq->padding, '0', STR_PAD_LEFT);
        });
    }
}
