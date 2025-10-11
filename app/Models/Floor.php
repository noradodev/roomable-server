<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Floor extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'property_id',
        'name',
        'floor_number',
    ];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

     protected static function booted()
    {
        static::deleting(function ($floor) {
            if ($floor->isForceDeleting()) {
                $floor->rooms()->withTrashed()->forceDelete();
            } else {
                $floor->rooms()->delete();
            }
        });

        static::restoring(function ($floor) {
            $floor->rooms()->withTrashed()->restore();
        });
    }


}
