<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'name',
        'location',
        'start_date',
        'end_date',
        'budget',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'budget' => 'decimal:2',
        ];
    }

    public function materials(): HasMany
    {
        return $this->hasMany(MasterMaterial::class);
    }

    public function cashFlows(): HasMany
    {
        return $this->hasMany(CashFlow::class);
    }

    public function photoReports(): HasMany
    {
        return $this->hasMany(PhotoReport::class);
    }
}
