<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'amount',
        'payment_method',
        'payment_status',
        'payment_date',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'datetime',
        ];
    }
}
