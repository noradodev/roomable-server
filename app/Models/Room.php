<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'floor_id',
        'room_number',
        'room_type',
        'price',
        'status',
    ];

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }
    public function activeTenant()
    {
        return $this->hasOne(Tenant::class)->where('status', 'active');
    }
    public function floor()
    {
        return $this->belongsTo(Floor::class);
    }


    protected static function booted()
    {
        static::deleting(function ($room) {
            if ($room->isForceDeleting()) {
                $room->tenants()->withTrashed()->forceDelete();
            } else {
                $room->tenants()->delete();
            }
        });

        static::restoring(function ($room) {
            $room->tenants()->withTrashed()->restore();
        });
    }
}
