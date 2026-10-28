<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyUsageSummary extends Model
{
	protected $guarded = [];

	protected $casts = [
		'usage_date' => 'date',
	];
}
