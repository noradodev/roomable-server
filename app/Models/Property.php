<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use HasUuid,  SoftDeletes;

    protected $fillable = [
        'landlord_id',
        'name',
        'address',
        'city',
        'description',
        'image_url'
    ];
    public function rooms()
    {
        return $this->hasManyThrough(Room::class, Floor::class, 'property_id', 'floor_id');
    }
    public function landlord(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function floors(): HasMany
    {
        return $this->hasMany(Floor::class);
    }
    public function getRouteKeyName(): string
    {
        return 'id';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'landlord_id');
    }
    protected static function booted()
    {
        static::deleting(function ($property) {
            if ($property->isForceDeleting()) {
                $property->floors()->withTrashed()->forceDelete();
            } else {
                $property->floors()->delete();
            }
        });

        static::restoring(function ($property) {
            $property->floors()->withTrashed()->restore();
        });
    }
     public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

  
}
