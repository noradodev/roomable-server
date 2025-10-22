<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasUuid;

    protected $fillable = [
        'tenant_id',
        'room_id',
        'amount',
        'month_years',
        "electricity_cost",
        'telegram_message_id',
        'proof_url',
        "water_cost",
        'paid_at',
        'method',
        'status',
        'note',
        'rejection_reason'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
    public function room(): BelongsTo 
    {
        return $this->belongsTo(Room::class);
    }
    protected static function booted()
    {
        static::updated(function ($payment) {
            if ($payment->isDirty('status') && $payment->status === 'paid') {
                dispatch(new \App\Jobs\SendPaymentNotifications($payment->id));
            }
        });
    }
}
