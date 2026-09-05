<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lapjusik extends Model
{
    protected $table = 'lapjusik';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['week_start' => 'date', 'progress_pct' => 'decimal:2'];
    }
}
