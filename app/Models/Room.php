<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'current_tenant_id',
        'price',
        'status',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
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
        static::updating(function ($room) {
            $oldTenantId = $room->getOriginal('current_tenant_id');
            $newTenantId = $room->current_tenant_id;

            if ($room->status !== 'maintenance') {
                if ($newTenantId && $newTenantId !== $oldTenantId) {
                    $room->status = 'occupied';

                    if ($tenant = \App\Models\Tenant::find($newTenantId)) {
                        $tenant->update(['status' => 'active']);
                    }

                    if ($oldTenantId && $oldTenantId !== $newTenantId) {
                        if ($oldTenant = \App\Models\Tenant::find($oldTenantId)) {
                            $oldTenant->update(['status' => 'inactive']);
                        }
                    }
                }

                if (is_null($newTenantId) && $oldTenantId) {
                    $room->status = 'available';

                    if ($oldTenant = \App\Models\Tenant::find($oldTenantId)) {
                        $oldTenant->update(['status' => 'inactive']);
                    }
                }
            } else {
                $room->current_tenant_id = null;

                if ($oldTenantId) {
                    if ($oldTenant = \App\Models\Tenant::find($oldTenantId)) {
                        $oldTenant->update(['status' => 'inactive']);
                    }
                }
            }
        });
    }
}
