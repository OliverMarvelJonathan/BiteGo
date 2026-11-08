<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    public const STATUS_LABELS = [
        'menunggu' => 'Menunggu',
        'dimasak' => 'Dimasak',
        'siap_ambil' => 'Siap Ambil',
        'selesai' => 'Selesai',
        'dibatalkan' => 'Dibatalkan',
    ];

    public const STATUS_NEXT = [
        'menunggu' => 'dimasak',
        'dimasak' => 'siap_ambil',
        'siap_ambil' => 'selesai',
    ];

    public const CANCELLABLE_STATUSES = ['menunggu'];

    protected $fillable = [
        'order_number', 'idempotency_key', 'user_id', 'stall_id', 'pickup_slot', 'pickup_code', 'pickup_date',
        'status', 'stock_deducted', 'total_amount', 'total_qty', 'notes',
    ];

    protected $casts = [
        'pickup_date' => 'date',
        'stock_deducted' => 'boolean',
    ];

    public function canTransitionTo(string $status): bool
    {
        return (self::STATUS_NEXT[$this->status] ?? null) === $status;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, self::CANCELLABLE_STATUSES, true);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function stall()
    {
        return $this->belongsTo(Stall::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getPickupSlotLabelAttribute(): string
    {
        return $this->pickup_slot === 'istirahat_2' ? 'Istirahat II' : 'Istirahat I';
    }

    public function getTotalAmountFormattedAttribute(): string
    {
        return 'Rp'.number_format($this->total_amount, 0, ',', '.');
    }
}
