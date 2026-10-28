<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
	protected $guarded = [];

	protected $casts = [
		'period_start' => 'datetime',
		'period_end' => 'datetime',
	];

	public function items()
	{
		return $this->hasMany(InvoiceItem::class);
	}
}
