<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SCurvePlanned extends Model
{
    protected $table = 's_curves_planned';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['week_start' => 'date', 'planned_progress_pct' => 'decimal:2'];
    }
}
