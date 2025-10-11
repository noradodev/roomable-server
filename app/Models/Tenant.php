<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasUuid, SoftDeletes;
    const STATUS_ACTIVE = 'active';
    const STATUS_UNASSIGNED = 'unassigned';
    const STATUS_MOVED_OUT = 'moved_out';

    protected $fillable = [
        'room_id',
        'name',
        'email',
        'move_in_date',
        'move_out_date',
        'phone',
        'telegram_id',
        'landlord_id',
        'status',
        'notes',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
    protected static function booted()
    {
        static::creating(function ($tenant) {
            $activeExists = Tenant::where('room_id', $tenant->room_id)
                ->where('status', 'active')
                ->exists();

            if ($activeExists) {
                throw new \Exception("This room already has an active tenant.");
            }
        });
    }
}
