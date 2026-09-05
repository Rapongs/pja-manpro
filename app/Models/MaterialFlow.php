<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialFlow extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['date' => 'date', 'in_qty' => 'decimal:2', 'out_qty' => 'decimal:2', 'balance_qty' => 'decimal:2'];
    }

    public function material(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(MasterMaterial::class, 'material_id');
    }
}
