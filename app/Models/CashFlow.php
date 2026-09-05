<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashFlow extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['date' => 'date', 'debit' => 'decimal:2', 'kredit' => 'decimal:2', 'balance' => 'decimal:2'];
    }
}
