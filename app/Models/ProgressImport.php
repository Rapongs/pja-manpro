<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgressImport extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'week_labels' => 'array',
            'target_values' => 'array',
            'actual_values' => 'array',
            'table_rows' => 'array',
            'imported_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
