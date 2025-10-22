<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasUuid, SoftDeletes;
    const STATUS_ACTIVE = 'active';
    const STATUS_UNASSIGNED = 'unassigned';
    const STATUS_MOVED_OUT = 'moved_out';

    protected $fillable = [
        'name',
        'email',
        'move_in_date',
        'due_date',
        'move_out_date',
        'rent_status',
        'phone',
        'telegram_id',
        'telegram_chat_id',
        'landlord_id',
        'status',
        'notes',
    ];
    protected $casts = [
        'due_date' => 'datetime:Y-m-d',
        'move_in_date' => 'datetime:Y-m-d',
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
    public function landlord()
    {
        return $this->belongsTo(User::class);
    }
    public function room()
    {
        return $this->hasOne(Room::class, 'current_tenant_id');
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
    public function currentRoom()
    {
        return $this->hasOne(\App\Models\Room::class, 'current_tenant_id', 'id');
    }
    public function scopeSearch($query, $search)
    {
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        return $query;
    }

    public function scopeStatus($query, $status)
    {
        if ($status) {
            $query->where('status', $status);
        }
        return $query;
    }

    public function scopeByProperty($query, $propertyId)
    {
        if ($propertyId) {
            $query->whereHas('currentRoom.floor.property', function ($q) use ($propertyId) {
                $q->where('id', $propertyId);
            });
        }
        return $query;
    }

    public function scopeMoveInDateRange($query, $from, $to)
    {
        if ($from && $to) {
            $query->whereBetween('move_in_date', [$from, $to]);
        }
        return $query;
    }
}
