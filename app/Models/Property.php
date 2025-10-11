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
    ];

    public function landlord(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function floors(): HasMany
    {
        return $this->hasMany(Floor::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function getRouteKeyName(): string
    {
        return 'id';
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
}
