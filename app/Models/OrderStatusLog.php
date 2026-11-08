<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderStatusLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'status',
        'notes',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return Order::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
