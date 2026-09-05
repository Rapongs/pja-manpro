<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhotoReport extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }
}
