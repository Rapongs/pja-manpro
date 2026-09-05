<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterMaterial extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['current_stock' => 'decimal:2'];
    }

    public function flows(): HasMany
    {
        return $this->hasMany(MaterialFlow::class, 'material_id');
    }

    public function procurementItems(): HasMany
    {
        return $this->hasMany(ProcurementItem::class, 'material_id');
    }
}
