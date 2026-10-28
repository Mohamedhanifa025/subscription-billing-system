<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsageEvent extends Model
{
    protected $guarded = [];

    protected $casts = [
        'usage_date' => 'date',
        'metadata' => 'array',
    ];
}
